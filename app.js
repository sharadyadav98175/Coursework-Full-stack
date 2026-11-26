document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('input[data-autocomplete-url]').forEach(input => {
    const url = input.dataset.autocompleteUrl;
    const listId = input.getAttribute('id') + '_list';
    let timeout = null;
    const container = document.createElement('div');
    container.className = 'autocomplete-container';
    input.parentNode.appendChild(container);

    input.addEventListener('input', () => {
      clearTimeout(timeout);
      const q = input.value.trim();
      if (!q) { container.innerHTML = ''; return; }
      timeout = setTimeout(() => {
        fetch(url + '?q=' + encodeURIComponent(q))
          .then(r => r.json())
          .then(data => {
            container.innerHTML = '';
            const ul = document.createElement('ul');
            data.forEach(item => {
              const li = document.createElement('li');
              li.textContent = item;
              li.addEventListener('click', () => {
                input.value = item;
                container.innerHTML = '';
              });
              ul.appendChild(li);
            });
            container.appendChild(ul);
          }).catch(err => {
            console.error('Autocomplete error', err);
            container.innerHTML = '';
          });
      }, 250);
    });

    document.addEventListener('click', (e) => {
      if (!container.contains(e.target) && e.target !== input) container.innerHTML = '';
    });
  });
});
