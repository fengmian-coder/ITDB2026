/*!
 * Color mode toggler for Bootstrap 5.3+
 * Based on https://getbootstrap.com/docs/5.3/customize/color-modes/
 */
(() => {
    'use strict'

    const getStoredTheme = () => localStorage.getItem('theme')
    const setStoredTheme = theme => localStorage.setItem('theme', theme)

    const getPreferredTheme = () => {
        const stored = getStoredTheme()
        if (stored) return stored
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }

    const setTheme = theme => {
        document.documentElement.setAttribute('data-bs-theme', theme)
    }

    const updateToggle = theme => {
        const toggle = document.getElementById('theme-toggle')
        if (!toggle) return
        const label = theme === 'dark' ? '日间模式' : '夜间模式'
        toggle.textContent = label
        toggle.setAttribute('aria-label', `切换到${label}`)
        toggle.setAttribute('title', `切换到${label}`)
    }

    setTheme(getPreferredTheme())

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (!getStoredTheme()) {
            const theme = getPreferredTheme()
            setTheme(theme)
            updateToggle(theme)
        }
    })

    document.addEventListener('DOMContentLoaded', () => {
        updateToggle(getPreferredTheme())

        const toggle = document.getElementById('theme-toggle')
        if (toggle) {
            toggle.addEventListener('click', () => {
                const newTheme = document.documentElement.getAttribute('data-bs-theme') === 'dark'
                    ? 'light'
                    : 'dark'
                setStoredTheme(newTheme)
                setTheme(newTheme)
                updateToggle(newTheme)
            })
        }
    })
})()
