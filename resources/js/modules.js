import Alpine from 'alpinejs'
import anchor from '@alpinejs/anchor'
import { navMenuData, navItemData } from "../alpine-nav/js/components/nav.js"

Alpine.plugin(anchor)
Alpine.data('navMenu', navMenuData)
Alpine.data('navItem', navItemData)

Alpine.start()
window.Alpine = Alpine
