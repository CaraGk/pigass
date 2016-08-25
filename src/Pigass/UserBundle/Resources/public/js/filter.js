/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François Angrand <pigass@medlibre.fr>
 * @copyright: Copyright 2015 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
*/

$(document).ready(function() {
    var hideAll = function () {
        for (var i = 1; i < 10; i++) {
            $('#pigass_registerbundle_filtertype_value_' + i).hide();
            $("label[for='pigass_registerbundle_filtertype_value_" + i + "']").hide();
        }
    }

    hideAll();

    $('#pigass_registerbundle_filtertype_question').change(function() {
        hideAll();
        $('#pigass_registerbundle_filtertype_value_' + $(this).val()).show();
    });
});
