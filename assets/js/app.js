/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François Angrand <pigass@medlibre.fr>
 * @copyright: Copyright 2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
*/

require('../css/app.scss');

const $ = require('jquery');
global.$ = global.jQuery = $;

require('bootstrap');

require('./confirm.js');

$(document).ready(function() {
    $('[data-toggle="popover"]').popover();
    $('[data-toggle="tooltip"]').tooltip();
});
