import * as $ from 'jquery';
import 'datatables';

export default (function () {
  $('#logDataTable').DataTable({
    'aoColumnDefs': [
      { 'bSortable': false, 'aTargets': [3, 4] },
    ],
    'order': [[0, 'desc']],
  });
  $('#dataTable').DataTable({
  });
}());
