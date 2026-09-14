(function () {
  'use strict';

  // Nur aktiv, wenn mindestens ein editierbares Belegungsraster auf der
  // Seite ist (Admin-Wochenansicht). Bewusst ohne Framework/Build-Schritt.
  var editableGrids = document.querySelectorAll('.day-grid-table-editable');
  if (editableGrids.length === 0) {
    return;
  }

  var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var baseUrl = (document.querySelector('meta[name="base-url"]') || {}).content || '';

  var dragSource = null; // { baseScheduleId, durationMinutes, grid }

  function addMinutes(hhmm, minutes) {
    var parts = hhmm.split(':');
    var total = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10) + minutes;
    total = ((total % 1440) + 1440) % 1440;
    var h = Math.floor(total / 60);
    var m = total % 60;
    return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
  }

  function clearDropTargets() {
    document.querySelectorAll('.cell.drop-target').forEach(function (el) {
      el.classList.remove('drop-target');
    });
  }

  document.addEventListener('dragstart', function (e) {
    var cell = e.target.closest ? e.target.closest('.cell[draggable="true"]') : null;
    if (!cell) {
      return;
    }
    dragSource = {
      baseScheduleId: cell.getAttribute('data-base-schedule-id'),
      durationMinutes: parseInt(cell.getAttribute('data-duration-minutes') || '30', 10),
      grid: cell.closest('.day-grid-table'),
    };
    cell.classList.add('dragging');
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', dragSource.baseScheduleId || '');
    }
  });

  document.addEventListener('dragend', function (e) {
    var cell = e.target.closest ? e.target.closest('.cell') : null;
    if (cell) {
      cell.classList.remove('dragging');
    }
    clearDropTargets();
    dragSource = null;
  });

  document.addEventListener('dragover', function (e) {
    if (!dragSource) {
      return;
    }
    var cell = e.target.closest ? e.target.closest('.cell[data-pitch-id]') : null;
    if (!cell || cell.closest('.day-grid-table') !== dragSource.grid) {
      return;
    }
    e.preventDefault();
    if (e.dataTransfer) {
      e.dataTransfer.dropEffect = 'move';
    }
    if (!cell.classList.contains('drop-target')) {
      clearDropTargets();
      cell.classList.add('drop-target');
    }
  });

  document.addEventListener('drop', function (e) {
    if (!dragSource) {
      return;
    }
    var cell = e.target.closest ? e.target.closest('.cell[data-pitch-id]') : null;
    if (!cell || cell.closest('.day-grid-table') !== dragSource.grid) {
      return;
    }
    e.preventDefault();
    clearDropTargets();

    var occurrenceDate = dragSource.grid.getAttribute('data-date');
    var newPitchId = cell.getAttribute('data-pitch-id');
    var newStartTime = cell.getAttribute('data-slot-start');
    var newEndTime = addMinutes(newStartTime, dragSource.durationMinutes);

    var body = new URLSearchParams({
      csrf_token: csrfToken,
      base_schedule_id: dragSource.baseScheduleId || '',
      occurrence_date: occurrenceDate || '',
      new_pitch_id: newPitchId || '',
      new_start_time: newStartTime || '',
      new_end_time: newEndTime,
    });

    fetch(baseUrl + '/admin/schedule-overrides', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, data: data };
        });
      })
      .then(function (result) {
        if (result.ok && result.data.status === 'ok') {
          window.location.reload();
        } else {
          alert('Verschieben fehlgeschlagen: ' + (result.data.message || 'Unbekannter Fehler'));
        }
      })
      .catch(function () {
        alert('Verschieben fehlgeschlagen: Netzwerkfehler.');
      });

    dragSource = null;
  });
})();
