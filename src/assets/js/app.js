// load in core egl javascript
require('../../../vendor/evergreen/generic/src/assets/js/core.js');

// for ticking all checkboxes
function tickall() {$('[type=checkbox').prop('checked', true).trigger('change').prev().val(1)};

console.log('loaded project js');
