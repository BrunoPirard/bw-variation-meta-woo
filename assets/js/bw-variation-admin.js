document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('meta-fields-container');
    const addButton = document.getElementById('add-field');

    if (addButton && container) {
        // Ajouter un nouveau champ
        addButton.addEventListener('click', function() {
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'meta-field';
            
            // Label input
            const labelInput = document.createElement('input');
            labelInput.type = 'text';
            labelInput.name = 'bw_variation_meta_fields[labels][]';
            labelInput.className = 'field-label';
            labelInput.placeholder = 'Field Label';

            // Description input
            const descInput = document.createElement('input');
            descInput.type = 'text';
            descInput.name = 'bw_variation_meta_fields[descriptions][]';
            descInput.placeholder = 'Field Description';

            // Template input
            const templateInput = document.createElement('input');
            templateInput.type = 'text';
            templateInput.name = 'bw_variation_meta_fields[templates][]';
            templateInput.className = 'field-template';
            templateInput.placeholder = 'Template Variable';

            // Hide checkbox wrapper
            const hideLabel = document.createElement('label');
            hideLabel.className = 'hide-field-label';

            // Hide checkbox
            const hideCheckbox = document.createElement('input');
            hideCheckbox.type = 'checkbox';
            hideCheckbox.name = 'bw_variation_meta_fields[hide][]';
            hideCheckbox.value = ''; // Sera mis à jour lors de la saisie du label

            // Hide label text
            const hideLabelText = document.createTextNode('Hide on product');
            hideLabel.appendChild(hideCheckbox);
            hideLabel.appendChild(hideLabelText);

            // Remove button
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'button remove-field';
            removeButton.textContent = 'Remove';

            // Ajouter les éléments au div
            fieldDiv.appendChild(labelInput);
            fieldDiv.appendChild(descInput);
            fieldDiv.appendChild(templateInput);
            fieldDiv.appendChild(hideLabel);
            fieldDiv.appendChild(removeButton);

            // Ajouter le div au container
            container.appendChild(fieldDiv);

            // Mettre à jour la valeur du checkbox et du template quand le label change
            labelInput.addEventListener('input', function() {
                const sanitizedValue = labelInput.value.toLowerCase().replace(/[^a-z0-9]/g, '_');
                hideCheckbox.value = sanitizedValue;
                templateInput.value = `data.variation.${sanitizedValue}_html`;
            });
        });

        // Supprimer un champ (utilisation de la délégation d'événements)
        container.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('remove-field')) {
                e.target.closest('.meta-field').remove();
            }
        });

        // Mettre à jour les valeurs des checkboxes et templates existants
        document.querySelectorAll('.meta-field').forEach(function(field) {
            const labelInput = field.querySelector('.field-label');
            const hideCheckbox = field.querySelector('input[type="checkbox"]');
            const templateInput = field.querySelector('.field-template');
            
            if (labelInput && hideCheckbox && templateInput) {
                labelInput.addEventListener('input', function() {
                    const sanitizedValue = labelInput.value.toLowerCase().replace(/[^a-z0-9]/g, '_');
                    hideCheckbox.value = sanitizedValue;
                    templateInput.value = `data.variation.${sanitizedValue}_html`;
                });
            }
        });
    }
});
