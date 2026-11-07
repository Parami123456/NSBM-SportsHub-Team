document.querySelectorAll('[data-confirm]').forEach((element) => {
  element.addEventListener('click', (event) => {
    const message = element.dataset.confirm;

    if (!window.confirm(message)) {
      event.preventDefault();
    }
  });
});