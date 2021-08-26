window.$ = require('jquery');
window.toastr = require('toastr');
window.flatpickr = require('flatpickr');
window.Cookies = require('js-cookie');
window.moment = require('moment');
window.numeral = require('numeral');
require('nanoscroller');
require('./egl/ui');
require('datatables.net');
require('datatables-bulma');
require('svgxuse');
require('selectize');
require('jszip');

require('../../../../../../node_modules/datatables.net-buttons/js/buttons.html5.js');
require('../../../../../../node_modules/datatables.net-responsive/js/dataTables.responsive.js');

require('../../../../../../node_modules/pdfmake/build/pdfmake.js');
require('../../../../../../node_modules/pdfmake/build/vfs_fonts.js');

import pdfMake from "pdfmake/build/pdfmake";
import pdfFonts from "pdfmake/build/vfs_fonts";
pdfMake.vfs = pdfFonts.pdfMake.vfs;



class Test {
    constructor(test) {
        this.testString = test;
        this.test();
    }

    test() {
        console.log(this.testString);
    }
}

const test = new Test("EGL Javascript initialised successfully");
