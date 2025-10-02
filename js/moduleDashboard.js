if (fpcm === undefined) {
    var fpcm = {};
}

fpcm.dashboard.onDone.extstatsChartData = {

    execAfter: function () {

        if (fpcm.vars.jsvars.extstatsChartData === undefined) {
            return false;
        }

        fpcm.ui_chart.draw(fpcm.vars.jsvars.extstatsChartData);
        return true;
    }

};