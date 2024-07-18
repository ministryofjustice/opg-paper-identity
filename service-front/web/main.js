import * as GOVUKFrontend from "govuk-frontend";
import MOJFrontend from "@ministryofjustice/frontend/moj/all.js";
import accessibleAutocomplete from "accessible-autocomplete";

function enhanceTemplateSearchElement(element) {
    if (element) {
        accessibleAutocomplete.enhanceSelectElement({
            selectElement: element,
            showAllValues: true,
            onConfirm: function (value) {
                // Provide default behaviour, which is normally overridden by `onConfirm`
                const requestedOption = [].filter.call(
                    this.selectElement.options,
                    (option) => (option.textContent || option.innerText) === value,
                )[0];
                if (requestedOption) {
                    requestedOption.selected = true;
                }

                this.selectElement.dispatchEvent(new CustomEvent("confirm"));
            },
        });
    }
}
GOVUKFrontend.initAll();
MOJFrontend.initAll();
