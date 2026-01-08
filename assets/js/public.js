(function($) {
    'use strict';

    const COOKIE_NAME = 'eat_cookies_consent';
    const CONSENT_EXPIRY = 365; // days

    const EatCookies = {
        previousConsent: null,

        init: function() {
            this.cacheDOM();
            this.bindEvents();
            this.checkConsent();
            this.adminScanner();
        },

        adminScanner: function() {
            if (!eatCookiesData.isAdmin) return;

            // Simple cookie parser
            const cookies = document.cookie.split(';').map(c => c.trim().split('=')[0]);
            if (cookies.length === 0) return;

            $.ajax({
                url: eatCookiesData.ajax_url,
                type: 'POST',
                data: {
                    action: 'eat_cookies_admin_scan',
                    nonce: eatCookiesData.scanNonce,
                    cookies: cookies,
                    url: window.location.href
                },
                success: function(response) {
                    if (response.success && response.data.new_cookies_found > 0) {
                        console.log('Eat Cookies: Admin scan found ' + response.data.new_cookies_found + ' new cookies.');
                    }
                }
            });
        },

        cacheDOM: function() {
            this.$popup = $('#eat-cookies-popup');
            this.$banner = $('#eat-cookies-banner');
            this.$trigger = $('#eat-cookies-trigger');
            this.$acceptAll = $('#eat-cookies-accept-all, #eat-cookies-banner-accept');
            this.$decline = $('#eat-cookies-decline, #eat-cookies-banner-decline');
            this.$settingsBtn = $('#eat-cookies-settings-btn');
            this.$showDetailsBtn = $('#eat-cookies-show-details');
            this.$settingsPanel = $('#eat-cookies-settings-panel');
            this.$saveSettings = $('#eat-cookies-save-settings, #eat-cookies-banner-save');
            this.$checkboxes = $('.category-checkbox');
            this.$tabBtns = $('.eat-cookies-tab-btn');
            this.$tabContents = $('.eat-cookies-tab-content');
            this.$closePopup = $('#eat-cookies-close');
        },

        bindEvents: function() {
            this.$acceptAll.on('click', () => this.handleAcceptAll());
            this.$decline.on('click', () => this.handleDecline());
            this.$settingsBtn.on('click', (e) => {
                e.preventDefault();
                // If settings panel is not active, switch to details tab first
                if (!this.$settingsPanel.hasClass('active')) {
                    this.switchTab('details');
                }
                this.$settingsPanel.toggleClass('active');

                // If opened, scroll it into view
                if (this.$settingsPanel.hasClass('active')) {
                    setTimeout(() => {
                        this.$settingsPanel[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                }
            });

            this.$showDetailsBtn.on('click', (e) => {
                e.preventDefault();
                this.$banner.removeClass('active');
                this.$popup.addClass('active');
            });

            this.$saveSettings.on('click', (e) => {
                e.preventDefault();
                this.handleSaveSettings();
            });
            
            this.$trigger.on('click', () => {
                this.$banner.addClass('active');
                this.$trigger.removeClass('active');
            });

            this.$tabBtns.on('click', (e) => {
                const tabId = $(e.currentTarget).data('tab');
                this.switchTab(tabId);
            });

            this.$closePopup.on('click', () => {
                this.$popup.removeClass('active');
                if (!this.getCookie(COOKIE_NAME)) {
                    this.$banner.addClass('active');
                } else {
                    this.$trigger.addClass('active');
                }
            });

            // Sync checkboxes between banner and modal
            $(document).on('change', '.category-checkbox', (e) => {
                const catId = $(e.target).attr('id').replace('banner-cat-', '').replace('cat-', '');
                const isChecked = $(e.target).is(':checked');
                $(`#banner-cat-${catId}, #cat-${catId}`).prop('checked', isChecked);
            });
        },

        switchTab: function(tabId) {
            this.$tabBtns.removeClass('active');
            $('.eat-cookies-tab-btn[data-tab="' + tabId + '"]').addClass('active');
            this.$tabContents.removeClass('active');
            $('#eat-cookies-tab-content-' + tabId).addClass('active');
        },

        checkConsent: function() {
            const consent = this.getCookie(COOKIE_NAME);
            if (!consent) {
                this.$banner.addClass('active');
                this.$trigger.removeClass('active');
                this.previousConsent = {
                    necessary: true,
                    functional: false,
                    analytics: false,
                    performance: false,
                    advertisement: false
                };
            } else {
                this.$trigger.addClass('active');
                const parsedConsent = JSON.parse(consent);
                this.previousConsent = parsedConsent;
                this.syncCheckboxes(parsedConsent);
                this.applyConsent(parsedConsent, true);
            }
        },

        syncCheckboxes: function(consent) {
            Object.keys(consent).forEach(category => {
                $(`#cat-${category}, #banner-cat-${category}`).prop('checked', consent[category]);
            });
        },

        handleAcceptAll: function() {
            this.$checkboxes.not(':disabled').prop('checked', true);
            const consent = {
                necessary: true,
                functional: true,
                analytics: true,
                performance: true,
                advertisement: true
            };
            this.saveConsent(consent);
        },

        handleDecline: function() {
            this.$checkboxes.not(':disabled').prop('checked', false);
            const consent = {
                necessary: true,
                functional: false,
                analytics: false,
                performance: false,
                advertisement: false
            };
            this.saveConsent(consent);
        },

        handleSaveSettings: function() {
            const consent = {
                necessary: true,
                functional: $('#cat-functional').is(':checked') || $('#banner-cat-functional').is(':checked'),
                analytics: $('#cat-analytics').is(':checked') || $('#banner-cat-analytics').is(':checked'),
                performance: $('#cat-performance').is(':checked') || $('#banner-cat-performance').is(':checked'),
                advertisement: $('#cat-advertisement').is(':checked') || $('#banner-cat-advertisement').is(':checked')
            };
            this.saveConsent(consent);
        },

        saveConsent: function(consent) {
            const consentId = this.generateId();
            this.setCookie(COOKIE_NAME, JSON.stringify(consent), CONSENT_EXPIRY);
            this.$popup.removeClass('active');
            this.$banner.removeClass('active');
            this.$trigger.addClass('active');

            // Determine if we need to reload (if any category was upgraded from false to true)
            let needsReload = false;
            if (this.previousConsent) {
                Object.keys(consent).forEach(cat => {
                    if (consent[cat] && !this.previousConsent[cat]) {
                        needsReload = true;
                    }
                });
            }

            this.previousConsent = consent;

            // Save to DB via AJAX
            $.ajax({
                url: eatCookiesData.ajax_url,
                type: 'POST',
                data: {
                    action: 'eat_cookies_save_consent',
                    nonce: eatCookiesData.nonce,
                    consent_id: consentId,
                    consent_state: consent
                },
                success: function(response) {
                    console.log('Consent saved to DB', response);
                    if (needsReload) {
                        window.location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to save consent to DB', error);
                    if (needsReload) {
                        window.location.reload();
                    }
                }
            });

            if (!needsReload) {
                this.applyConsent(consent);
            }
        },

        applyConsent: function(consent, isInitial = false) {
            // Load scripts based on consent
            if (!isInitial || !eatCookiesData.scriptsRendered) {
                Object.keys(consent).forEach(category => {
                    if (consent[category] && eatCookiesData.scripts[category]) {
                        this.injectScripts(eatCookiesData.scripts[category]);
                    }
                });
            }

            // Clear cookies for declined categories
            this.clearCookies(consent);

            $(document).trigger('eat_cookies_consent_applied', [consent]);
        },

        clearCookies: function(consent) {
            const categoriesToClear = Object.keys(consent).filter(cat => cat !== 'necessary' && !consent[cat]);
            if (categoriesToClear.length === 0) return;

            const domain = window.location.hostname;
            const path = '/';
            
            // Get all current cookies to check for pattern matches
            const currentCookies = document.cookie.split(';').map(c => c.trim().split('=')[0]);

            categoriesToClear.forEach(category => {
                const cookiesInCategory = eatCookiesData.cookies[category] || [];
                
                // 1. Clear explicitly listed cookies
                cookiesInCategory.forEach(cookieInfo => {
                    const cookieName = cookieInfo.name;
                    this.removeCookie(cookieName, domain, path);
                });

                // 2. Clear by pattern for known services if category is declined
                if (category === 'analytics') {
                    currentCookies.forEach(name => {
                        if (name.indexOf('_ga') === 0 || name.indexOf('_gid') === 0 || name.indexOf('_hj') === 0) {
                            this.removeCookie(name, domain, path);
                        }
                    });
                }
                if (category === 'advertisement') {
                    currentCookies.forEach(name => {
                        if (name.indexOf('_fbp') === 0 || name.indexOf('_gcl') === 0 || name.indexOf('tk_ai') === 0) {
                            this.removeCookie(name, domain, path);
                        }
                    });
                }
            });
        },

        removeCookie: function(name, domain, path) {
            // Standard removal
            const base = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=" + path + ";";
            document.cookie = base;
            document.cookie = base + " domain=" + domain + ";";
            document.cookie = base + " domain=." + domain + ";";

            // Try common subdomains/root variations if it's a multi-level domain
            const domainParts = domain.split('.');
            if (domainParts.length >= 2) {
                // Try from current domain up to root
                for (let i = 0; i <= domainParts.length - 2; i++) {
                    const currentDomain = domainParts.slice(i).join('.');
                    document.cookie = base + " domain=" + currentDomain + ";";
                    document.cookie = base + " domain=." + currentDomain + ";";
                }
            }
            
            // Special handling for GA cookies which often have unique paths or domains
            if (name.indexOf('_ga') === 0 || name.indexOf('_gid') === 0) {
                const rootDomain = domainParts.slice(-2).join('.');
                document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=." + rootDomain + ";";
            }
        },

        injectScripts: function(scriptsHtml) {
            if (!scriptsHtml) return;
            const div = document.createElement('div');
            div.innerHTML = scriptsHtml;
            Array.from(div.querySelectorAll('script')).forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                document.head.appendChild(newScript);
            });
        },

        setCookie: function(name, value, days) {
            let expires = "";
            if (days) {
                const date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
        },

        getCookie: function(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        },

        generateId: function() {
            return 'ec_' + Math.random().toString(36).substr(2, 9);
        }
    };

    $(document).ready(function() {
        EatCookies.init();
    });

})(jQuery);
