// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Add user AMD module.
 *
 * @module      local_course_studentreports/adduser
 * @author      2023 Derek Wilson <wilsondc5@appstate.edu>
 * @copyright   (c) 2023 Appalachian State University, Boone, NC
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import * as DynamicTable from 'core_table/dynamic';
import * as Str from 'core/str';
import Fragment from 'core/fragment';
import ModalEvents from 'core/modal_events';
import Notification from 'core/notification';
import Pending from 'core/pending';
import Prefetch from 'core/prefetch';
import ModalSaveCancel from 'core/modal_save_cancel';

const Selectors = {
    cohortSelector: "#id_cohortlist",
    triggerButtons: ".addusersbutton [type='submit']",
    unwantedHiddenFields: "input[value='_qf__force_multiselect_submission']",
    buttonWrapper: '[data-region="wrapper"]',
};

/**
 * Get the content of the body for the specified context.
 *
 * @param {Number} contextId
 * @returns {Promise}
 */
const getBodyForContext = contextId => {
    return Fragment.loadFragment('local_course_studentreports', 'add_users_form', contextId, {});
};

/**
 * Get the dynamic table for the button.
 *
 * @param {HTMLElement} element
 * @returns {HTMLElement}
 */
const getDynamicTableForElement = element => {
    const wrapper = element.closest(Selectors.buttonWrapper);

    return DynamicTable.getTableFromId(wrapper.dataset.tableUniqueid);
};

/**
 * Register the event listeners for this contextid.
 *
 * @param {Number} contextId
 */
const registerEventListeners = contextId => {
    document.addEventListener('click', e => {
        if (e.target.closest(Selectors.triggerButtons)) {
            e.preventDefault();

            showModal(getDynamicTableForElement(e.target), contextId);

            return;
        }
    });
};

/**
 * Display the modal for this contextId.
 *
 * @param {HTMLElement} dynamicTable The table to beb refreshed when changes are made
 * @param {Number} contextId
 * @returns {Promise}
 */
const showModal = (dynamicTable, contextId) => {
    const pendingPromise = new Pending('local_course_studentreports/adduser:showModal');

    return ModalSaveCancel.create({
        large: true,
        title: Str.get_string('adduser', 'local_course_studentreports'),
        body: getBodyForContext(contextId),
        buttons: {
            save: Str.get_string('adduser', 'local_course_studentreports'),
        },
        show: true,
    })
        .then(modal => {
            modal.getRoot().on(ModalEvents.save, e => {
                // Trigger a form submission, so that any mform elements can do final tricks before the form submission
                // is processed.
                // The actual submit event is captured in the next handler.

                e.preventDefault();
                modal.getRoot().find('form').submit();
            });

            modal.getRoot().on('submit', 'form', e => {
                e.preventDefault();

                submitForm(dynamicTable, modal);
            });

            modal.getRoot().on(ModalEvents.hidden, () => {
                modal.destroy();
            });

            return modal;
        })
        .then(modal => Promise.all([modal, modal.getBodyPromise()]))
        .then(modal => {
            pendingPromise.resolve();

            return modal;
        })
        .catch(Notification.exception);
};

/**
 * Submit the form via ajax.
 *
 * @param {HTMLElement} dynamicTable
 * @param {Object} modal
 */
const submitForm = (dynamicTable, modal) => {
    // Note: We use a jQuery object here so that we can use its serialize functionality.
    const form = modal.getRoot().find('form');

    // Before send the data through AJAX, we need to parse and remove some unwanted hidden fields.
    // This hidden fields are added automatically by mforms and when it reaches the AJAX we get an error.
    form.get(0).querySelectorAll(Selectors.unwantedHiddenFields).forEach(hiddenField => hiddenField.remove());

    modal.hide();
    modal.destroy();

    return Promise.all([
        DynamicTable.refreshTableContent(dynamicTable),
    ]);
};

/**
 * Set up quick enrolment for the manual enrolment plugin.
 *
 * @param {Number} contextid The context id to setup for
 */
export const init = ({contextid}) => {
    registerEventListeners(contextid);

    Prefetch.prefetchStrings('local_course_studentreports', [
        'adduser',
    ]);

    Prefetch.prefetchString('enrol', 'totalenrolledusers');
};
