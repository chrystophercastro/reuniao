/**
 * MeetingRoom Manager - Main JavaScript
 */

$(document).ready(function () {

    // =============================================
    // Inicializar Select2 para participantes
    // =============================================
    if ($('#resParticipants').length) {
        $('#resParticipants').select2({
            theme: 'bootstrap-5',
            placeholder: 'Selecione os participantes',
            allowClear: true,
            dropdownParent: $('#reservationModal')
        });
    }

    // =============================================
    // Inicializar FullCalendar
    // =============================================
    const calendarEl = document.getElementById('calendar');
    let calendar = null;

    if (calendarEl && typeof FullCalendar !== 'undefined') {
        calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'pt-br',
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            allDaySlot: false,
            navLinks: true,
            editable: true,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            nowIndicator: true,
            height: 'auto',
            slotDuration: '00:30:00',
            expandRows: true,
            stickyHeaderDates: true,
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                meridiem: false,
                hour12: false
            },

            // Buscar eventos do servidor
            events: function (info, successCallback, failureCallback) {
                const roomFilter = $('#roomFilter').val();
                let url = APP_CONFIG.apiBase + '/get_reservations.php';
                if (roomFilter) {
                    url += '?room_id=' + roomFilter;
                }

                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function (data) {
                        successCallback(data);
                    },
                    error: function () {
                        failureCallback();
                        showToast('Erro ao carregar eventos', 'danger');
                    }
                });
            },

            // Clicar em um espaço vazio para criar
            select: function (info) {
                openCreateModal(info.startStr, info.endStr);
            },

            // Clicar em um evento existente
            eventClick: function (info) {
                openViewModal(info.event);
            },

            // Arrastar e soltar evento
            eventDrop: function (info) {
                updateEventDates(info.event, info.revert);
            },

            // Redimensionar evento
            eventResize: function (info) {
                updateEventDates(info.event, info.revert);
            },

            // Tooltip do evento
            eventDidMount: function (info) {
                const props = info.event.extendedProps;
                info.el.title = info.event.title + '\n📍 ' + (props.room_name || '') + '\n👤 ' + (props.creator_name || '');
            }
        });

        calendar.render();

        // Filtro por sala
        $('#roomFilter').on('change', function () {
            calendar.refetchEvents();
        });
    }

    // =============================================
    // Botão Nova Reunião
    // =============================================
    $('#btnNewReservation').on('click', function () {
        const now = new Date();
        const start = formatDateTimeLocal(now);
        const endDate = new Date(now.getTime() + 60 * 60 * 1000);
        const end = formatDateTimeLocal(endDate);
        openCreateModal(start, end);
    });

    // =============================================
    // Abrir modal de criação
    // =============================================
    function openCreateModal(start, end) {
        $('#modalTitle').html('<i class="bi bi-calendar-plus me-2"></i>Nova Reunião');
        $('#reservationId').val('');
        $('#resTitle').val('');
        $('#resDescription').val('');
        $('#resRoom').val('');
        $('#resStart').val(formatDateTimeLocal(new Date(start)));
        $('#resEnd').val(formatDateTimeLocal(new Date(end)));
        $('#resParticipants').val(null).trigger('change');
        $('#btnDeleteReservation').hide();

        const modal = new bootstrap.Modal(document.getElementById('reservationModal'));
        modal.show();
    }

    // =============================================
    // Abrir modal de visualização
    // =============================================
    function openViewModal(event) {
        const props = event.extendedProps;
        const startDate = new Date(event.start);
        const endDate = new Date(event.end);

        $('#viewModalTitle').text(event.title);
        $('#viewModalHeader').css('background-color', event.backgroundColor || '#2563EB');
        $('#viewRoom').text(props.room_name || '');
        $('#viewDate').text(formatDate(startDate));
        $('#viewTime').text(formatTime(startDate) + ' - ' + formatTime(endDate));
        $('#viewCreator').text(props.creator_name || '');

        if (props.description) {
            $('#viewDescriptionBlock').show();
            $('#viewDescription').text(props.description);
        } else {
            $('#viewDescriptionBlock').hide();
        }

        // Buscar participantes
        $.ajax({
            url: APP_CONFIG.apiBase + '/get_reservation.php?id=' + event.id,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.participants && data.participants.length > 0) {
                    $('#viewParticipantsBlock').show();
                    let html = '';
                    data.participants.forEach(function (p) {
                        html += '<span class="badge bg-primary me-1 mb-1">' + escapeHtml(p.name) + '</span>';
                    });
                    $('#viewParticipants').html(html);
                } else {
                    $('#viewParticipantsBlock').hide();
                }
            }
        });

        // Mostrar botões de editar/excluir se for o criador ou admin
        if (props.created_by == APP_CONFIG.currentUserId || APP_CONFIG.currentUserRole === 'admin') {
            $('#btnViewEdit').show().off('click').on('click', function () {
                bootstrap.Modal.getInstance(document.getElementById('viewReservationModal')).hide();
                openEditModal(event);
            });
            $('#btnViewDelete').show().off('click').on('click', function () {
                if (confirm('Tem certeza que deseja excluir esta reunião?')) {
                    deleteReservation(event.id);
                }
            });
        } else {
            $('#btnViewEdit').hide();
            $('#btnViewDelete').hide();
        }

        const modal = new bootstrap.Modal(document.getElementById('viewReservationModal'));
        modal.show();
    }

    // =============================================
    // Abrir modal de edição
    // =============================================
    function openEditModal(event) {
        const props = event.extendedProps;

        $('#modalTitle').html('<i class="bi bi-pencil me-2"></i>Editar Reunião');
        $('#reservationId').val(event.id);
        $('#resTitle').val(event.title);
        $('#resDescription').val(props.description || '');
        $('#resRoom').val(props.room_id);
        $('#resStart').val(formatDateTimeLocal(new Date(event.start)));
        $('#resEnd').val(formatDateTimeLocal(new Date(event.end)));
        $('#btnDeleteReservation').show();

        // Buscar participantes atuais
        $.ajax({
            url: APP_CONFIG.apiBase + '/get_reservation.php?id=' + event.id,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.participants) {
                    const ids = data.participants.map(function (p) { return p.id; });
                    $('#resParticipants').val(ids).trigger('change');
                }
            }
        });

        const modal = new bootstrap.Modal(document.getElementById('reservationModal'));
        modal.show();
    }

    // =============================================
    // Salvar reserva (criar ou editar)
    // =============================================
    $('#btnSaveReservation').on('click', function () {
        const id = $('#reservationId').val();
        const data = {
            title: $('#resTitle').val(),
            description: $('#resDescription').val(),
            room_id: $('#resRoom').val(),
            start: $('#resStart').val().replace('T', ' '),
            end: $('#resEnd').val().replace('T', ' '),
            participants: $('#resParticipants').val() || []
        };

        // Validação básica no client
        if (!data.title.trim()) {
            showToast('Informe o título da reunião', 'warning');
            return;
        }
        if (!data.room_id) {
            showToast('Selecione uma sala', 'warning');
            return;
        }
        if (!data.start || !data.end) {
            showToast('Informe as datas de início e fim', 'warning');
            return;
        }

        const url = id
            ? APP_CONFIG.apiBase + '/update_reservation.php'
            : APP_CONFIG.apiBase + '/create_reservation.php';

        if (id) {
            data.id = id;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Salvando...');

        $.ajax({
            url: url,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('reservationModal')).hide();
                    if (calendar) {
                        calendar.refetchEvents();
                    }
                } else {
                    showToast(response.message, 'danger');
                }
            },
            error: function (xhr) {
                let msg = 'Erro ao salvar a reunião';
                try {
                    const resp = JSON.parse(xhr.responseText);
                    if (resp.debug) msg += ': ' + resp.debug;
                    else if (resp.message) msg = resp.message;
                } catch(e) {}
                showToast(msg, 'danger');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Salvar');
            }
        });
    });

    // =============================================
    // Excluir reserva (botão no modal de edição)
    // =============================================
    $('#btnDeleteReservation').on('click', function () {
        const id = $('#reservationId').val();
        if (id && confirm('Tem certeza que deseja excluir esta reunião?')) {
            deleteReservation(id);
        }
    });

    // =============================================
    // Função: excluir reserva
    // =============================================
    function deleteReservation(id) {
        $.ajax({
            url: APP_CONFIG.apiBase + '/delete_reservation.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ id: id }),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    // Fechar modais abertos
                    $('.modal').modal('hide');
                    if (calendar) {
                        calendar.refetchEvents();
                    }
                } else {
                    showToast(response.message, 'danger');
                }
            },
            error: function () {
                showToast('Erro ao excluir a reunião', 'danger');
            }
        });
    }

    // =============================================
    // Função: atualizar datas via drag/drop
    // =============================================
    function updateEventDates(event, revert) {
        const data = {
            id: event.id,
            start: formatDateTimeISO(event.start),
            end: formatDateTimeISO(event.end)
        };

        $.ajax({
            url: APP_CONFIG.apiBase + '/update_reservation.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast('Reunião movida com sucesso!', 'success');
                } else {
                    showToast(response.message, 'danger');
                    revert();
                }
            },
            error: function () {
                showToast('Erro ao mover a reunião', 'danger');
                revert();
            }
        });
    }

    // =============================================
    // Utilitários
    // =============================================

    function formatDateTimeLocal(date) {
        if (typeof date === 'string') {
            date = new Date(date);
        }
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    function formatDateTimeISO(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

    function formatDate(date) {
        return String(date.getDate()).padStart(2, '0') + '/' +
               String(date.getMonth() + 1).padStart(2, '0') + '/' +
               date.getFullYear();
    }

    function formatTime(date) {
        return String(date.getHours()).padStart(2, '0') + ':' +
               String(date.getMinutes()).padStart(2, '0');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    // =============================================
    // Toast notification
    // =============================================
    window.showToast = function (message, type = 'info') {
        const icons = {
            success: 'bi-check-circle-fill',
            danger: 'bi-exclamation-triangle-fill',
            warning: 'bi-exclamation-circle-fill',
            info: 'bi-info-circle-fill'
        };

        const toastHtml = `
            <div class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${icons[type] || icons.info} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        const container = document.getElementById('toastContainer');
        if (container) {
            container.insertAdjacentHTML('beforeend', toastHtml);
            const toastEl = container.lastElementChild;
            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
        }
    };

    // =============================================
    // Sidebar toggle (mobile)
    // =============================================
    $('.navbar-toggler').on('click', function () {
        $('#sidebar').toggleClass('show');
    });

    // Fechar sidebar ao clicar fora (mobile)
    $(document).on('click', function (e) {
        if ($(window).width() < 992) {
            if (!$(e.target).closest('#sidebar, .navbar-toggler').length) {
                $('#sidebar').removeClass('show');
            }
        }
    });

});
