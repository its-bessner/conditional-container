import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['body', 'rowTemplate', 'row'];

    rowSnapshots = new Map();

    rowTemplateTargetConnected(template) {
        // We need to queue a micro task here, so that Stimulus will fire
        // rowTargetConnected().
        queueMicrotask(() => {this._unwrap(template)});
    }

    rowTargetConnected() {
        this._makeSortable();
    }

    rowTargetDisconnected(row) {
        this.rowSnapshots.delete(row);
        this._makeSortable();
    }

    copy(event) {
        const row = this._getRow(event);
        const snapshot = this.rowSnapshots.get(row);

        row.insertAdjacentHTML('afterend', snapshot);
        const newRow = row.nextElementSibling;
        this.rowSnapshots.set(newRow, snapshot);

        this._syncInputs(row, newRow);
    }

    delete(event) {
        if(this.bodyTarget.children.length > 1) {
            this._getRow(event).remove();
        } else {
            this._resetInputs(this._getRow(event));
        }
    }

    enable(event) {
        event.target.previousElementSibling.checked ^= 1;
    }

    move(event) {
        const row = this._getRow(event);

        if (event.code === 'ArrowUp' || event.keyCode === 38) {
            event.preventDefault();

            if (row.previousElementSibling) {
                row.previousElementSibling.insertAdjacentElement('beforebegin', row);
            } else {
                this.bodyTarget.insertAdjacentElement('beforeend', row);
            }

            event.target.focus();
        } else if (event.code === 'ArrowDown' || event.keyCode === 40) {
            event.preventDefault();

            if (row.nextElementSibling) {
                row.nextElementSibling.insertAdjacentElement('afterend', row);
            } else {
                this.bodyTarget.insertAdjacentElement('afterbegin', row);
            }

            event.target.focus();
        }
    }

    /**
     * This method is specific to the row wizard being a "module wizard".
     */
    updateModuleWizardLink(event) {
        const row = this._getRow(event);
        const link = row.querySelector('.module_link');
        const images = row.querySelectorAll('img.module_image');
        const select = event.target;

        link.href = link.href.replace(/id=[0-9]+/, 'id=' + select.value);

        if (select.value > 0) {
            link.classList.remove('hidden');

            images.forEach((image) => {
                image.classList.add('hidden');
            });
        } else {
            link.classList.add('hidden');

            images.forEach((image) => {
                image.classList.remove('hidden');
            });
        }
    }

    beforeCache() {
        // Restore the original HTML with template tags before Turbo caches the
        // page. They will get unwrapped again at the restored page.
        this.rowTargets.forEach(row => {
            this._wrap(row);
        });
    }

    _unwrap(template) {
        this.rowSnapshots.set(template.content.querySelector('*[data-contao--row-wizard-target="row"]'), template.innerHTML);

        template.replaceWith(template.content);
    }

    _wrap(row) {
        const template = document.createElement('template');
        template.setAttribute('data-contao--row-wizard-target', 'rowTemplate');
        template.innerHTML = this.rowSnapshots.get(row);

        this._syncInputs(row, template.content.querySelector('tr'));

        row.replaceWith(template);
    }

    _getRow(event) {
        return event.target.closest('*[data-contao--row-wizard-target="row"]');
    }

    _syncInputs(rowFrom, rowTo) {
        const selectsFrom = rowFrom.querySelectorAll('input:not(.choices__input--cloned), select');
        const selectsTo = rowTo.querySelectorAll('input, select');

        for (let i = 0; i < selectsFrom.length; i++) {
            selectsTo[i].value = selectsFrom[i].value;
        }
    }

    _resetInputs(row) {
        row.querySelectorAll('input').forEach((input) => {
            input.value = '';
        });

        row.querySelectorAll('select').forEach((select) => {
            select.value = select.children[0].value;
        });
    }

    _makeSortable() {
        Array.from(this.bodyTarget.children).forEach((tr, i) => {
            tr.querySelectorAll('input, select').forEach((el) => {
                el.name = el.name.replace(/\[[0-9]+]/g, '[' + i + ']');
            });
        });

        // TODO: replace this with a vanilla JS solution
        new Sortables(this.bodyTarget, {
            constrain: true,
            opacity: 0.6,
            handle: '.drag-handle',
            onComplete: () => {
                this._makeSortable(this.bodyTarget);
            }
        });
    };
}
