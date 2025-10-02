if (fpcm === undefined) {
    var fpcm = {};
}

fpcm.extStats = {

    init: function () {

        fpcm.worker.postMessage({
            namespace: 'extStats',
            function: 'drawChart',
            id: 'extStats.drawChart'
        });

        fpcm.worker.postMessage({
            namespace: 'extStats',
            function: 'drawList',
            id: 'extStats.drawList'
        });

    },

    drawChart: function () {

        if (!fpcm.vars.jsvars.extStats.chart) {
            return true;
        }

        window.chart = fpcm.ui_chart.draw(fpcm.vars.jsvars.extStats.chart);
    },

    drawList: function () {

        if (!fpcm.vars.jsvars.extStats.hasList) {
            fpcm.dom.fromId('fpcm-id-extstats-list-spinner').remove();
            return true;
        }

        if (fpcm.dataview !== undefined) {
            fpcm.dataview.render('extendedstats-list', {
                onRenderAfter: () => {
                    fpcm.dom.fromId('fpcm-id-extstats-list-spinner').remove();
                    fpcm.dom.fromId('fpcm-id-extstats-list-leadline').removeClass('d-none');
                }
            });
        }

        fpcm.dom.bindClick('.fpcm-extstats-links-delete', function (_ev, _ui) {

            var _entryId = parseInt(_ui.dataset.entry);
            _ui.firstElementChild.classList.replace('fa-trash', 'fa-spinner');
            _ui.firstElementChild.classList.add('fa-spin');

            fpcm.ajax.post('extstats/delete', {
                quiet: true,
                data: {
                    id: _entryId,
                    obj: fpcm.vars.jsvars.extStats.delList
                },
                execDone: function (result) {

                    _ui.firstElementChild.classList.remove('fa-spin');
                    if (!result.code) {
                        _ui.firstElementChild.classList.replace('fa-spinner', 'fa-ban');
                        _ui.firstElementChild.classList.add('text-danger');
                        return false;
                    }

                    _ui.parentElement.parentElement.remove();
                }
            });

            return false;
        });

    }

};
