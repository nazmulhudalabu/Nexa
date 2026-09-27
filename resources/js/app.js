import './bootstrap';
document.addEventListener('click', (event) => {
	const toggle = event.target.closest('.inline-reaction-toggle');

	if (toggle) {
		event.stopPropagation();
		const menu = toggle.nextElementSibling;

		document.querySelectorAll('.inline-reaction-menu:not([hidden])').forEach((openMenu) => {
			if (openMenu !== menu) {
				openMenu.hidden = true;
			}
		});

		menu.hidden = !menu.hidden;
		return;
	}

	document.querySelectorAll('.inline-reaction-menu:not([hidden])').forEach((menu) => {
		if (!menu.parentElement.contains(event.target)) {
			menu.hidden = true;
		}
	});
});
