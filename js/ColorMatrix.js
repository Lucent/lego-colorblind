/* Color Matrix Library : v1.0 : 2007/04/19 */
/* http://www.nofunc.com/Color_Matrix_Library/ */

new function(_) {

color={};

color.matrix_r={
    'Blind':function(v) {
        return({'Normal':[1,0,0,0,0, 0,1,0,0,0, 0,0,1,0,0, 0,0,0,1,0, 0,0,0,0,1],
                'Protanopia':[0.567,0.433,0,0,0, 0.558,0.442,0,0,0, 0,0.242,0.758,0,0, 0,0,0,1,0, 0,0,0,0,1],
                'Protanomaly':[0.817,0.183,0,0,0, 0.333,0.667,0,0,0, 0,0.125,0.875,0,0, 0,0,0,1,0, 0,0,0,0,1],
                'Deuteranopia':[0.625,0.375,0,0,0, 0.7,0.3,0,0,0, 0,0.3,0.7,0,0, 0,0,0,1,0, 0,0,0,0,1],
                'Deuteranomaly':[0.8,0.2,0,0,0, 0.258,0.742,0,0,0, 0,0.142,0.858,0,0, 0,0,0,1,0, 0,0,0,0,1],
                'Tritanopia':[0.95,0.05,0,0,0, 0,0.433,0.567,0,0, 0,0.475,0.525,0,0, 0,0,0,1,0, 0,0,0,0,1],
                'Tritanomaly':[0.967,0.033,0,0,0, 0,0.733,0.267,0,0, 0,0.183,0.817,0,0, 0,0,0,1,0, 0,0,0,0,1],
                'Achromatopsia':[0.299,0.587,0.114,0,0, 0.299,0.587,0.114,0,0, 0.299,0.587,0.114,0,0, 0,0,0,1,0, 0,0,0,0,1],
                'Achromatomaly':[0.618,0.320,0.062,0,0, 0.163,0.775,0.062,0,0, 0.163,0.320,0.516,0,0,0,0,0,1,0,0,0,0,0]}[v]);

    }
};

color.matrix=function(o,m) {
	o[3] = 255;
    var r=((o[0]*m[0])+(o[1]*m[1])+(o[2]*m[2])+(o[3]*m[3])+m[4]);
    var g=((o[0]*m[5])+(o[1]*m[6])+(o[2]*m[7])+(o[3]*m[8])+m[9]);
    var b=((o[0]*m[10])+(o[1]*m[11])+(o[2]*m[12])+(o[3]*m[13])+m[14]);
    var a=((o[0]*m[15])+(o[1]*m[16])+(o[2]*m[17])+(o[3]*m[18])+m[19]);

    return([r<0?0:(r<255?r:255), g<0?0:(g<255?g:255), b<0?0:(b<255?b:255)]);//, 'A':a<0?0:(a<255?a:255)});
};

};