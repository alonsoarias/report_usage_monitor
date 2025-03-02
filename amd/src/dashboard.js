/**
 * JavaScript para la interacción del dashboard.
 *
 * @module     report_usage_monitor/dashboard
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    /**
     * Inicializa los componentes interactivos del dashboard.
     */
    var init = function() {
        registerEventListeners();
    };

    /**
     * Registra los listeners de eventos para los elementos interactivos.
     */
    var registerEventListeners = function() {
        // Hover para tarjetas en el dashboard
        $('.stat-card').hover(
            function() {
                $(this).css('transform', 'translateY(-5px)');
                $(this).css('transition', 'transform 0.3s ease');
                $(this).css('box-shadow', '0 4px 8px rgba(0, 0, 0, 0.1)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
                $(this).css('box-shadow', '0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)');
            }
        );
        
        // Manejo de pestañas en la sección de usuarios
        $('#userTabs a').on('click', function(e) {
            e.preventDefault();
            $(this).tab('show');
        });
    };

    return {
        init: init
    };
});