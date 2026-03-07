<div class="wm-nav__wrap">
	<nav
		x-data="navMenu({ strategy: 'drill-down',  hasMobileMenu: true, maxDepth: 5 })"
		x-cloak
		class="wm-nav wm-nav__item--strategy-drill-down wm-nav--custom"
		id="primary-nav"
	>
		<div class="wm-nav__container">
			<div x-show="!(hasMobileMenu && isMobile)" class="">
				<?php
				display_alpine_navigation(array(
					'theme_location' => 'primary',
				));
				?>
			</div>
		</div>
		<div class="wm-nav__mobile text-white">
			<button @click="toggleMobileMenu()" class="wm-nav__mobile-toggle">
				<span></span>
			</button>
			<!-- Mobile overlay -->
			<div
				x-show="mobileOpen"
				x-transition:enter="wm-nav__transition--enter"
				x-transition:enter-start="wm-nav__transition--enter-start"
				x-transition:enter-end="wm-nav__transition--enter-end"
				x-transition:leave="wm-nav__transition--leave"
				x-transition:leave-start="wm-nav__transition--leave-start"
				x-transition:leave-end="wm-nav__transition--leave-end"
				@click="toggleMobileMenu()"
				class="wm-nav__mobile-overlay"
				x-cloak="wm-nav__mobile"
			></div>

			<!-- Mobile panel -->
			<div
				x-show="mobileOpen"
				x-transition:enter="wm-nav__transition--enter"
				x-transition:enter-start="wm-nav__transition--enter-start"
				x-transition:enter-end="wm-nav__transition--enter-end"
				x-transition:leave="wm-nav__transition--leave"
				x-transition:leave-start="wm-nav__transition--leave-start"
				x-transition:leave-end="wm-nav__transition--leave-end"
				class="wm-nav__mobile-panel"
				@click.stop
				x-cloak="wm-nav__mobile"
			>
				<div class="wm-nav__mobile-header">
					<!-- Top row with back and close buttons -->
					<div class="wm-nav__mobile-header__top-row">
						<div class="wm-nav__mobile-header__back-btn">
							<button
								x-show="currentView.level > 0"
								@click="navigateMobileMenuBack()"
								:disabled="animating"
								x-transition:enter="wm-nav__transition--enter"
								x-transition:enter-start="wm-nav__transition--enter-start"
								x-transition:enter-end="wm-nav__transition--enter-end"
								x-transition:leave="wm-nav__transition--leave"
								x-transition:leave-start="wm-nav__transition--leave-start"
								x-transition:leave-end="wm-nav__transition--leave-end"
							>
								<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
								</svg>
								<span x-text="viewStack[viewStack.length - 1]?.title || 'Back'"></span>
							</button>
						</div>
						<div class="wm-nav__mobile-header__close-btn">
							<button @click="toggleMobileMenu()">
								<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
								</svg>
							</button>
						</div>
					</div>

					<!-- Bottom row with title (gets full width) -->
					<div class="wm-nav__mobile-header__title-row">
						<div class="wm-nav__mobile-header__title">
							<span x-text="currentView.title"></span>
						</div>
					</div>
				</div>
				<div class="wm-nav__mobile-body">
					<div x-ref="currentContainer" class="wm-nav__mobile-body__content">
						<ul>
							<template x-for="item in currentView.items" :key="'current-' + item.id">
								<li>
									<div>
										<a :href="item.link" x-text="item.label"></a>
										<button
											x-show="itemHasChildren(item)"
											@click="navigateMobileMenuTo({title: item.label, items: item.children, level: currentView.level + 1})"
											:disabled="animating"
										>
											<div><span></span></div>
										</button>
									</div>
								</li>
							</template>
						</ul>
					</div>
					<div x-ref="stagingContainer" x-show="stagedView" class="wm-nav__mobile-staging hidden">
						<ul>
							<template x-for="item in stagedView?.items || []" :key="'staged-' + item.id">
								<li>
									<div class="wm-nav__mobile-staging__item--animating">
										<span x-text="item.label"></span>
									</div>
								</li>
							</template>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</nav>
</div>