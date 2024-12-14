document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form.variations_form');
    if (!form) return;

    // Utiliser le template WooCommerce
    const template = wp.template('variation-template-custom-meta');

    // Écouter l'événement de variation trouvée
    form.addEventListener('show_variation', function(event) {
        const variation = event.detail;
        const templateHtml = template({
            variation: variation
        });

        const container = form.querySelector('.woocommerce-variation-custom-meta');
        if (container) {
            container.innerHTML = templateHtml;
            container.style.display = 'block';
        }
    });

    // Écouter l'événement de réinitialisation
    form.addEventListener('hide_variation', function() {
        const container = form.querySelector('.woocommerce-variation-custom-meta');
        if (container) {
            container.style.display = 'none';
        }
    });
});