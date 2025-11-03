


/*=============================================================
    Authour URI: www.binarycart.com
    License: Commons Attribution 3.0

    http://creativecommons.org/licenses/by/3.0/

    100% To use For Personal And Commercial Use.
    IN EXCHANGE JUST GIVE US CREDITS AND TELL YOUR FRIENDS ABOUT US
   
    ========================================================  */


(function ($) {
    "use strict";
    var mainApp = {

        main_fun: function () {
            /*====================================
            METIS MENU 
            ======================================*/
            $('#main-menu').metisMenu();

            /*====================================
              LOAD APPROPRIATE MENU BAR
           ======================================*/
            $(window).bind("load resize", function () {
                if ($(this).width() < 768) {
                    $('div.sidebar-collapse').addClass('collapse')
                } else {
                    $('div.sidebar-collapse').removeClass('collapse')
                }
            });

            /*====================================
            MORRIS BAR CHART
         ======================================*/
            if (typeof Morris !== 'undefined' && $('#morris-bar-chart').length) {
                Morris.Bar({
                    element: 'morris-bar-chart',
                data: [{
                    y: 'Jan',
                    a: 100,
                    b: 90
                }, {
                    y: 'Feb',
                    a: 75,
                    b: 65
                }, {
                    y: 'Mar',
                    a: 50,
                    b: 40
                }, {
                    y: 'Apr',
                    a: 75,
                    b: 65
                }, {
                    y: 'May',
                    a: 50,
                    b: 40
                }, {
                    y: 'Apr',
                    a: 75,
                    b: 65
                }, {
                    y: 'May',
                    a: 100,
                    b: 90
                }],
                xkey: 'y',
                ykeys: ['a', 'b'],
                labels: ['Female', 'Male'],
                hideHover: 'auto',
                resize: true
            });
            }

            /*====================================
          MORRIS DONUT CHART
       ======================================*/
            if (typeof Morris !== 'undefined' && $('#morris-donut-chart').length) {
                Morris.Donut({
                    element: 'morris-donut-chart',
                data: [{
                    label: "Technical Skills",
                    value: 12
                }, {
                    label: "Leadership",
                    value: 30
                }, {
                    label: "Basic Life",
                    value: 20
                }],
                resize: true
            });
            }

            /*====================================
         MORRIS AREA CHART
      ======================================*/
            if (typeof Morris !== 'undefined' && $('#morris-area-chart').length) {
                Morris.Area({
                    element: 'morris-area-chart',
                data: [{
                    period: '2019 Q1',
                    '20 to 35 Year olds' : 2666,
                    '35 to 37 Year olds': null,
                    '18 to 20 Year olds': 2647
                }, {
                    period: '2019 Q2',
                   '20 to 35 Year olds': 2778,
                    '35 to 37 Year olds': 2294,
                    '18 to 20 Year olds': 2441
                }, {
                    period: '2019 Q3',
                    '20 to 35 Year olds': 4912,
                    '35 to 37 Year olds': 1969,
                    '18 to 20 Year olds': 2501
                }, {
                    period: '2019 Q4',
                    '20 to 35 Year olds': 3767,
                    '35 to 37 Year olds': 3597,
                    '18 to 20 Year olds': 5689
                }, {
                    period: '2020 Q1',
                    '20 to 35 Year olds': 6810,
                    '35 to 37 Year olds': 1914,
                    '18 to 20 Year olds': 2293
                }, {
                    period: '2020 Q2',
                    '20 to 35 Year olds': 5670,
                    '35 to 37 Year olds': 4293,
                    '18 to 20 Year olds': 1881
                }, {
                    period: '2020 Q3',
                    '20 to 35 Year olds': 4820,
                    '35 to 37 Year olds': 3795,
                    '18 to 20 Year olds': 1588
                }, {
                    period: '2020 Q4',
                    '20 to 35 Year olds': 15073,
                    '35 to 37 Year olds': 5967,
                    '18 to 20 Year olds': 5175
                }, {
                    period: '2021 Q1',
                    '20 to 35 Year olds': 10687,
                    '35 to 37 Year olds': 4460,
                    '18 to 20 Year olds': 2028
                }, {
                    period: '2021 Q2',
                    '20 to 35 Year olds': 8432,
                    '35 to 37 Year olds': 5713,
                    '18 to 20 Year olds': 1791
                }],
                xkey: 'period',
                ykeys: ['20 to 35 Year olds', '35 to 37 Year olds', '18 to 20 Year olds'],
                labels: ['20 to 35 Year olds', '35 to 37 Year olds', '18 to 20 Year olds'],
                pointSize: 2,
                hideHover: 'auto',
                resize: true
            });
            }

            /*====================================
    MORRIS LINE CHART
 ======================================*/
            if (typeof Morris !== 'undefined' && $('#morris-line-chart').length) {
                Morris.Line({
                    element: 'morris-line-chart',
                data: [{
                    y: '2015',
                    a: 100,
                    b: 90
                }, {
                    y: '2015',
                    a: 75,
                    b: 65
                }, {
                    y: '2016',
                    a: 50,
                    b: 40
                }, {
                    y: '2017',
                    a: 75,
                    b: 65
                }, {
                    y: '2018',
                    a: 50,
                    b: 40
                }, {
                    y: '2019',
                    a: 55,
                    b: 75
                }, {
                    y: '2020',
                    a: 60,
                    b: 30
                }],
                xkey: 'y',
                ykeys: ['a', 'b'],
                labels: ['Females', 'Males'],
                hideHover: 'auto',
                resize: true
            });
            }


        },

        initialization: function () {
            mainApp.main_fun();

        }

    }
    // Initializing ///

    $(document).ready(function () {
        mainApp.main_fun();
    });

}(jQuery));
