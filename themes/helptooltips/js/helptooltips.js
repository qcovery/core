function displayHelp (helpClass) {
    $('.'+helpClass).each(function () {
        let potisionTarget = false;
        if ($(this).data('target') !== undefined) {
            potisionTarget = $($(this).data('target'));
        }

        let positionX = 0;
        if ($(this).data('x') !== undefined) {
            positionX = $($(this).data('x'))[0];
        }

        let positionY = 0;
        if ($(this).data('y') !== undefined) {
            positionY = $($(this).data('y'))[0];
        }

        $(this).qtip({
            content: {
                attr: 'data-helpContent',
                button: true
            },
            show: {
                event: false,
                ready: true
            },
            hide: {
                fixed: true,
                leave: false,
                event: false
            },
            events: {
                show: function (event, api) {
                    var $el = $(api.elements.target[0]);
                    $el.qtip('option', 'position.my', $el.data('help_my_position') || 'top center');
                    $el.qtip('option', 'position.at', $el.data('help_at_position') || 'bottom center');
                }
            },
            style: {
                classes: 'qtip-rounded'
            },
            position: {
                target: potisionTarget,
                adjust: {
                    x: positionX,
                    y: positionY
                }
            }
        })
    });
}

$(document).ready(function() {
    console.log('HelpTooltips');
    if (typeof showHelp !== 'undefined') {
    		if (showHelp) {
        		displayHelp('showHelp');
    		}
    }
});
