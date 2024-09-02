import * as GOVUKFrontend from "govuk-frontend";
import MOJFrontend from "@ministryofjustice/frontend/moj/all.js";
import accessibleAutocomplete from "accessible-autocomplete";

GOVUKFrontend.initAll();
MOJFrontend.initAll();

const $autocompletes = document.querySelectorAll(
  '[data-module="app-natural-autocomplete"]'
);
Array.from($autocompletes).forEach(($autocomplete) => {
  accessibleAutocomplete.enhanceSelectElement({
    selectElement: $autocomplete,
    showAllValues: true,
    autoselect: false,
  });
});
