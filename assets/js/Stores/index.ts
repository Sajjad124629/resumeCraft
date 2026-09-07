import { defineStore } from 'pinia';
import appSetting from '@/app-setting';
export const useAppStore = defineStore('app', {
    state: () => {
        const savedTheme = typeof window !== 'undefined' ? (localStorage.getItem('theme') || 'light') : 'light';
        let isDark = savedTheme === 'dark';
        if (savedTheme === 'system' && typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            isDark = true;
        }
        return {
            isDarkMode: isDark,
            mainLayout: 'app',
            theme: savedTheme,
            menu: typeof window !== 'undefined' ? (localStorage.getItem('menu') || 'vertical') : 'vertical',
            layout: typeof window !== 'undefined' ? (localStorage.getItem('layout') || 'full') : 'full',
            rtlClass: typeof window !== 'undefined' ? (localStorage.getItem('rtlClass') || 'ltr') : 'ltr',
            animation: typeof window !== 'undefined' ? (localStorage.getItem('animation') || '') : '',
            navbar: typeof window !== 'undefined' ? (localStorage.getItem('navbar') || 'navbar-sticky') : 'navbar-sticky',
            locale: 'en',
            sidebar: false,
            languageList: [
                { code: 'zh', name: 'Chinese' },
                { code: 'da', name: 'Danish' },
                { code: 'en', name: 'English' },
                { code: 'fr', name: 'French' },
                { code: 'de', name: 'German' },
                { code: 'el', name: 'Greek' },
                { code: 'hu', name: 'Hungarian' },
                { code: 'it', name: 'Italian' },
                { code: 'ja', name: 'Japanese' },
                { code: 'pl', name: 'Polish' },
                { code: 'pt', name: 'Portuguese' },
                { code: 'ru', name: 'Russian' },
                { code: 'es', name: 'Spanish' },
                { code: 'sv', name: 'Swedish' },
                { code: 'tr', name: 'Turkish' },
                { code: 'ae', name: 'Arabic' },
            ],
            isShowMainLoader: true,
            semidark: typeof window !== 'undefined' ? (localStorage.getItem('semidark') === 'true') : false,
        };
    },
    actions: {
        setMainLayout(payload: any = null) {
            this.mainLayout = payload;
        },
        toggleTheme(payload: any = null) {
            payload = payload || this.theme;
            if (typeof window !== 'undefined') {
                localStorage.setItem('theme', payload);
            }
            this.theme = payload;
            if (payload == 'light') {
                this.isDarkMode = false;
            } else if (payload == 'dark') {
                this.isDarkMode = true;
            } else if (payload == 'system') {
                if (typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    this.isDarkMode = true;
                } else {
                    this.isDarkMode = false;
                }
            }
            if (typeof window !== 'undefined') {
                if (this.isDarkMode) {
                    document.querySelector('body')?.classList.add('dark');
                } else {
                    document.querySelector('body')?.classList.remove('dark');
                }
            }
        },
        toggleMenu(payload: any = null) {
            payload = payload || this.menu;
            this.sidebar = false;
            if (typeof window !== 'undefined') {
                localStorage.setItem('menu', payload);
            }
            this.menu = payload;
        },
        toggleLayout(payload: any = null) {
            payload = payload || this.layout;
            if (typeof window !== 'undefined') {
                localStorage.setItem('layout', payload);
            }
            this.layout = payload;
        },
        toggleRTL(payload: any = null) {
            payload = payload || this.rtlClass;
            this.rtlClass = payload;

            if (typeof window !== 'undefined') {
                localStorage.setItem('rtlClass', this.rtlClass);
                document.querySelector('html')?.setAttribute('dir', this.rtlClass || 'ltr');
            }
        },
        toggleAnimation(payload: any = null) {
            payload = payload?.trim() || this.animation;
            if (typeof window !== 'undefined') {
                localStorage.setItem('animation', payload);
                appSetting.changeAnimation();
            }
            this.animation = payload;
        },
        toggleNavbar(payload: any = null) {
            payload = payload || this.navbar;
            if (typeof window !== 'undefined') {
                localStorage.setItem('navbar', payload);
            }
            this.navbar = payload;
        },
        toggleSemidark(payload: any = null) {
            payload = payload || false;
            if (typeof window !== 'undefined') {
                localStorage.setItem('semidark', payload);
            }
            this.semidark = payload;
        },
        toggleSidebar() {
            this.sidebar = !this.sidebar;
        },
        toggleMainLoader() {
            this.isShowMainLoader = true;
            if (typeof window !== 'undefined') {
                setTimeout(() => {
                    this.isShowMainLoader = false;
                }, 500);
            }
        },
    },
    getters: {}
});
