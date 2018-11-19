/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François Angrand <pigass@medlibre.fr>
 * @copyright: Copyright 2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
*/

const $ = require('jquery');
require('raphael');
require('jquery-mousewheel');
const mapael = require('jquery-mapael');
require('./france_regions.min.js');

var areaMap = {};
document.addEventListener('DOMContentLoaded', function() {
    var elements = document.querySelectorAll('.area-map');
    [].forEach.call(elements, function(area) {
        $.extend(areaMap, { [area.dataset.id]: { slug: area.dataset.slug, name: area.dataset.name, logo: area.dataset.logo } });
    })
});

$(function(){
    $('.description').hide();

    $('.structures').mapael({
        map: {
            name: "france_regions",
            defaultArea: {
                attrs: { fill: "#AAAAAA" },
                attrsHover: { fill: "#EABB8E" },
                href: "#",
                eventHandlers: {
                    click: function (e, id, mapElem, textElem, elemOptions) {
                        $('.description').hide();
                        if (typeof elemOptions.myPanel != 'undefined') {
                            $('.structure_'+elemOptions.myPanel).fadeIn('slow');
                        }
                    }
                }
            },
            defaultPlot: {
                type: "image",
                witdh: 40,
                height: 40,
                attrs: { opacity: 1 },
                attrsHover: { fill: "#EABB8E" },
                href: "#",
                eventHandlers: {
                    click: function (e, id, mapElem, textElem, elemOptions) {
                        $('.description').hide();
                        if (typeof elemOptions.myPanel != 'undefined') {
                            $('.structure_'+elemOptions.myPanel).fadeIn('slow');
                        }
                    },
                    mouseover: function (e, id, mapElem, textElem, elemOptions) {
                        $("path[data-id='" + elemOptions.plotsOn + "']").attr("fill", "#EABB8E");
                    },
                    mouseout: function (e, id, mapElem, textElem, elemOptions) {
                        $("path[data-id='" + elemOptions.plotsOn + "']").attr("fill", "#72CAE9");
                    },
                }
            }
        },
        areas: {
            "region-53": (typeof areaMap.bretagne != 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.bretagne.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Bretagne : " + areaMap.bretagne.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Bretagne</span>"}
            },
            "region-52": (typeof areaMap.paysdelaloire !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.paysdelaloire.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Pays-de-la-Loire : " + areaMap.paysdelaloire.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Pays-de-la-Loire</span>"}
            },
            "region-25": (typeof areaMap.bassenormandie !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.bassenormandie.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Basse-Normandie : " + areaMap.bassenormandie.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Basse-Normandie</span>"}
            },
            "region-54": (typeof areaMap.poitoucharentes !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.poitoucharentes.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Poitou-Charentes : " + areaMap.poitoucharentes.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Poitou-Charentes</span>"}
            },
            "region-72": (typeof areaMap.aquitaine !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.aquitaine.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Aquitaine : " + areaMap.aquitaine.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Aquitaine</span>"}
            },
            "region-73": (typeof areaMap.midipyrenees !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.midipyrenees.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Midi-Pyrénées : " + areaMap.midipyrenees.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Midi-Pyrénées</span>"}
            },
            "region-91": (typeof areaMap.languedocroussillon !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.languedocroussillon.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Languedoc-Roussillon : " + areaMap.languedocroussillon.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Languedoc-Roussillon</span>"}
            },
            "region-24": (typeof areaMap.centre !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.centre.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Centre : " + areaMap.centre.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Centre</span>"}
            },
            "region-23": (typeof areaMap.hautenormandie !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.hautenormandie.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Haute-Normandie : " + areaMap.hautenormandie.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Haute-Normandie</span>"}
            },
            "region-22": (typeof areaMap.picardie !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.picardie.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Picardie : " + areaMap.picardie.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Picardie</span>"}
            },
            "region-11": (typeof areaMap.iledefrance !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.iledefrance.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Île-de-France : " + areaMap.iledefrance.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Île-de-France</span>"}
            },
            "region-74": (typeof areaMap.limousin !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.limousin.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Limousin : " + areaMap.limousin.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Limousin</span>"}
            },
            "region-83": (typeof areaMap.auvergne !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.auvergne.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Auvergne : " + areaMap.auvergne.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Auvergne</span>"}
            },
            "region-82": (typeof areaMap.rhonesalpes !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.rhonesalpes.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Rhones-Alpes : " + areaMap.rhonesalpes.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Rhones-Alpes</span>"}
            },
            "region-93": (typeof areaMap.paca !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.paca.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Provence-Alpes-Côte-d'Azur : " + areaMap.paca.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Provence-Alpes-Côte-d'Azur</span>"}
            },
            "region-26": (typeof areaMap.bourgogne !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.bourgogne.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Bourgogne : " + areaMap.bourgogne.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Bourgogne</span>"}
            },
            "region-21": (typeof areaMap.champagneardennes !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.champagneardennes.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Champagne-Ardennes : " + areaMap.champagneardennes.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Champagne-Ardennes</span>"}
            },
            "region-31": (typeof areaMap.nordpasdecalais !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.nordpasdecalais.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Nord-Pas-de-Calais : " + areaMap.nordpasdecalais.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Nord-Pas-de-Calais</span>"}
            },
            "region-41": (typeof areaMap.lorraine !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.lorraine.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Lorraine : " + areaMap.lorraine.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Lorraine</span>"}
            },
            "region-42": (typeof areaMap.alsace !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.alsace.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Alsace : " + areaMap.alsace.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Alsace</span>"}
            },
            "region-43": (typeof areaMap.franchecomte !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.franchecomte.slug,
                tooltip: {content: "<span style=\"font-weight:bold;\">Franche-Comté : " + areaMap.franchecomte.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight:bold;\">Franche-Comté</span>"}
            },
            "region-94": (typeof areaMap.corse !== 'undefined') ? {
                href: "#",
                attrs: {
                    fill: "#72CAE9"
                },
                myPanel : areaMap.corse.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Corse : " + areaMap.corse.name + "</span>"}
            } : {
                href: "#",
                tooltip: {content: "<span style=\"font-weight: bold\">Corse</span>"}
            }
        },
        plots: {
            "plot-region-53": (typeof areaMap.bretagne !== 'undefined') ? {
                plotsOn: "region-53",
                type: "image",
                url: areaMap.bretagne.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.bretagne.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Bretagne : " + areaMap.bretagne.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-52": (typeof areaMap.paysdelaloire !== 'undefined') ? {
                plotsOn: "region-52",
                type: "image",
                url: areaMap.paysdelaloire.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.paysdelaloire.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Pays-de-la-Loire : " + areaMap.paysdelaloire.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-25": (typeof areaMap.bassenormandie !== 'undefined') ? {
                plotsOn: "region-25",
                type: "image",
                url: areaMap.bassenormandie.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.bassenormandie.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Basse-Normandie : " + areaMap.bassenormandie.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-54": (typeof areaMap.poitoucharentes !== 'undefined') ? {
                plotsOn: "region-54",
                type: "image",
                url: areaMap.poitoucharentes.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.poitoucharentes.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Poitou-Charentes : " + areaMap.poitoucharentes.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-72": (typeof areaMap.aquitaine !== 'undefined') ? {
                plotsOn: "region-72",
                type: "image",
                url: areaMap.aquitaine.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.aquitaine.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Aquitaine : " + areaMap.aquitaine.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-73": (typeof areaMap.midipyrenees !== 'undefined') ? {
                plotsOn: "region-73",
                type: "image",
                url: areaMap.midipyrenees.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.midipyrenees.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Midi-Pyrénées : " + areaMap.midipyrenees.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-91": (typeof areaMap.languedocroussillon !== 'undefined') ? {
                plotsOn: "region-91",
                type: "image",
                url: areaMap.languedocroussillon.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.languedocroussillon.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Languedoc-Roussillon : " + areaMap.languedocroussillon.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-24": (typeof areaMap.centre !== 'undefined') ? {
                plotsOn: "region-24",
                type: "image",
                url: areaMap.centre.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.centre.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Centre : " + areaMap.centre.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-23": (typeof areaMap.hautenormandie !== 'undefined') ? {
                plotsOn: "region-23",
                type: "image",
                url: areaMap.hautenormandie.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.hautenormandie.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Haute-Normandie : " + areaMap.hautenormandie.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-22": (typeof areaMap.picardie !== 'undefined') ? {
                plotsOn: "region-22",
                type: "image",
                url: areaMap.picardie.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.picardie.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Picardie : " + areaMap.picardie.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-11": (typeof areaMap.iledefrance !== 'undefined') ? {
                plotsOn: "region-11",
                type: "image",
                url: areaMap.iledefrance.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.iledefrance.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Île-de-France : " + areaMap.iledefrance.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-74": (typeof areaMap.limousin !== 'undefined') ? {
                plotsOn: "region-74",
                type: "image",
                url: areaMap.limousin.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.limousin.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Limousin : " + areaMap.limousin.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-83": (typeof areaMap.auvergne !== 'undefined') ? {
                plotsOn: "region-83",
                type: "image",
                url: areaMap.auvergne.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.auvergne.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Auvergne : " + areaMap.auvergne.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-82": (typeof areaMap.rhonesalpes !== 'undefined') ? {
                plotsOn: "region-82",
                type: "image",
                url: areaMap.rhonesalpes.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.rhonesalpes.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Rhônes-Alpes : " + areaMap.rhonesalpes.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-93": (typeof areaMap.paca !== 'undefined') ? {
                plotsOn: "region-93",
                type: "image",
                url: areaMap.paca.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.paca.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Provence-Alpes-Côte-d'Azur : " + areaMap.paca.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-26": (typeof areaMap.bourgogne !== 'undefined') ? {
                plotsOn: "region-26",
                type: "image",
                url: areaMap.bourgogne.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.bourgogne.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Bourgogne : " + areaMap.bourgogne.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-21": (typeof areaMap.champagneardennes !== 'undefined') ? {
                plotsOn: "region-21",
                type: "image",
                url: areaMap.champagneardennes.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.champagneardennes.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Champagne-Ardennes : " + areaMap.champagneardennes.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-31": (typeof areaMap.nordpasdecalais !== 'undefined') ? {
                plotsOn: "region-31",
                type: "image",
                url: areaMap.nordpasdecalais.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.nordpasdecalais.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Nord-Pas-de-Calais : " + areaMap.nordpasdecalais.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-41": (typeof areaMap.lorraine !== 'undefined') ? {
                plotsOn: "region-41",
                type: "image",
                url: areaMap.lorraine.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.lorraine.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Lorraine : " + areaMap.lorraine.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-42": (typeof areaMap.alsace !== 'undefined') ? {
                plotsOn: "region-42",
                type: "image",
                url: areaMap.alsace.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.alsace.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Alsace : " + areaMap.alsace.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-43": (typeof areaMap.franchecomte !== 'undefined') ? {
                plotsOn: "region-43",
                type: "image",
                url: areaMap.franchecomte.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.franchecomte.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Franche-Comté : " + areaMap.franchecomte.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
            "plot-region-94": (typeof areaMap.corse !== 'undefined') ? {
                plotsOn: "region-94",
                type: "image",
                url: areaMap.corse.logo,
                width: 40,
                height: 40,
                myPanel : areaMap.corse.slug,
                tooltip: {content: "<span style=\"font-weight: bold\">Corse : " + areaMap.corse.name + "</span>"},
                attrs: {
                    opacity: 1
                },
                attrHover: {
                    transform: "s1.5"
                }
            } : {},
        }
    });
});
