/* global Vue */
import {copyTextToClipboard, datepicker, datetimepicker, getCsrfParam, getCsrfToken, initModal, selectpicker, show, showError, showErrorNotify, svgIcon} from "./utils";
import {addPushHandlers, removePushHandlers} from "./push-notify";
import {AudioPlay} from "./AudioPlay";

let _RequestForm = null;

export function RequestForm (...a) {
    if (_RequestForm === null) {
        _RequestForm = new RequestFormClass(...a);
        _RequestForm.init();
    }
    return _RequestForm;
}

class RequestFormClass {
    //some code
    baseInit() {
        //some code
        initModal(".js-modal-transcription", {
            beforeShow: function () {
                let _modal = this;

                selectpicker(this);

                _modal.on('click', '.js-do-transcription', function (e) {
                    e.preventDefault();
                    const $button = $(this);
                    $button.prop('disabled');
                    const $errorDiv = _modal.find('.js-transcription-error');
                    const entityId = $button.data('entity-id');
                    const entityType = $button.data('entity-type');
                    const url = $button.data('url');
                    const $pushWrap = _modal.find('.js-wrap-push');
                    const $spinner = _modal.find('.js-transcription-spinner');

                    $errorDiv.hide().text('');
                    $pushWrap.hide().text('');
                    $spinner.show();

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            entityId: entityId,
                            entityType: entityType,
                            _csrf: yii.getCsrfToken()
                        },
                        success: function (response) {
                            if (response.success) {
                                addPushHandlers("call-transcription-complete", function (data) {
                                    if (data.entityId == entityId && data.entityType == entityType) {
                                        $.ajax({
                                            url: $button.data("complete-url"),
                                            type: "GET",
                                            data: {id: entityId},
                                            success: function (response) {
                                                $pushWrap.html(response).show();
                                                $spinner.hide();
                                                $button.remove();
                                                _modal.find('.js-disclaimer').remove();
                                                $.notify(
                                                    { message: 'Транскрибация завершена!', target: '_self' },
                                                    { type: 'success', delay: 5000 }
                                                );
                                            }
                                        });

                                        removePushHandlers("call-transcription-complete");
                                        removePushHandlers("call-transcription-error");
                                    }
                                });

                                // Обработчик ошибок
                                addPushHandlers("call-transcription-error", function (data) {
                                    if (data.entityId == entityId && data.entityType == entityType) {
                                        $errorDiv.text(data.error).show();
                                        $pushWrap.hide();
                                        $spinner.hide();
                                        $button.prop('disabled', false);
                                        $.notify(
                                            { message: 'Ошибка транскрибации', target: '_self' },
                                            { type: 'danger', delay: 5000 }
                                        );
                                        removePushHandlers("call-transcription-complete");
                                        removePushHandlers("call-transcription-error");
                                    }
                                });
                            } else {
                                $errorDiv.text(response.error).show();
                                $spinner.hide();
                                $button.prop('disabled', false);
                            }
                        },
                        error: function () {
                            $errorDiv.text('Произошла ошибка при отправке запроса').show();
                            $spinner.hide();
                            $button.prop('disabled', false);
                        }
                    });
                });

                _modal.on('click', '.js-do-ai', function (e) {
                    e.preventDefault();
                    const $button = $(this);
                    $button.prop('disabled');
                    const $errorDiv = _modal.find('.js-data-ai-error');
                    const entityId = $button.data('entity-id');
                    const entityType = $button.data('entity-type');
                    const url = $button.data('url');
                    const $pushWrap = _modal.find('.js-wrap-push-ai');
                    const $spinner = _modal.find('.js-ai-spinner');
                    const prompt = _modal.find('.js-prompt').val();

                    $errorDiv.hide().text('');
                    $pushWrap.hide().text('');
                    $spinner.show();

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            entityId: entityId,
                            entityType: entityType,
                            prompt: encodeURIComponent(prompt),
                            _csrf: yii.getCsrfToken()
                        },
                        success: function (response) {
                            if (response.success) {
                                addPushHandlers("call-summary-complete", function (data) {
                                    if (data.entityId == entityId && data.entityType == entityType) {
                                        $.ajax({
                                            url: $button.data("complete-url"),
                                            type: "GET",
                                            data: {id: entityId},
                                            success: function (response) {
                                                $pushWrap.html(response).show();
                                                $spinner.hide();
                                                $button.prop("disabled", false);
                                                $.notify(
                                                    { message: 'Обработка ИИ завершена!', target: '_self' },
                                                    { type: 'success', delay: 5000 }
                                                );
                                            }
                                        });

                                        removePushHandlers("call-summary-complete");
                                        removePushHandlers("call-summary-error");
                                    }
                                });

                                // Обработчик ошибок
                                addPushHandlers("call-summary-error", function (data) {
                                    if (data.entityId == entityId && data.entityType == entityType) {
                                        $errorDiv.text(data.error).show();
                                        $pushWrap.hide();
                                        $spinner.hide();
                                        $button.prop('disabled', false);
                                        $.notify(
                                            { message: 'Ошибка при обработки ИИ', target: '_self' },
                                            { type: 'danger', delay: 5000 }
                                        );
                                        removePushHandlers("call-summary-complete");
                                        removePushHandlers("call-summary-error");
                                    }
                                });
                            } else {
                                $errorDiv.text(response.error).show();
                                $spinner.hide();
                                $button.prop('disabled', false);
                            }
                        },
                        error: function () {
                            $errorDiv.text('Произошла ошибка при отправке запроса').show();
                            $spinner.hide();
                            $button.prop('disabled', false);
                        }
                    });
                });

                _modal.on('click', '.js-do-data-ai-check-list', function (e) {
                    e.preventDefault();
                    const $button = $(this);
                    $button.prop('disabled');
                    const $errorDiv = _modal.find('.js-data-ai-check-list-error');
                    const url = $button.data('url');
                    const $spinner = _modal.find('.js-data-ai-check-list-spinner');
                    const callTranscriptionId = $button.data('call-transcription-id');
                    const checkListId = _modal.find('select#js-check-list').val();
                    const list = _modal.find('#js-call-transcription-check-list');

                    list.hide().text('');
                    $errorDiv.hide().text('');
                    $spinner.show();

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            callTranscriptionId: callTranscriptionId,
                            checkListId: checkListId,
                            _csrf: yii.getCsrfToken()
                        },
                        success: function (response) {
                            if (response.success) {
                                addPushHandlers("data-ai-check-list-complete", function (data) {
                                    if (data.callTranscriptionId == callTranscriptionId && data.checkListId == checkListId) {
                                        $.ajax({
                                            url: $button.data("complete-url"),
                                            type: "GET",
                                            data: {id: callTranscriptionId},
                                            success: function (response) {
                                                list.html(response).show();
                                                $spinner.hide();
                                                $button.prop("disabled", false);
                                                $.notify(
                                                    { message: 'Обработка ИИ завершена!', target: '_self' },
                                                    { type: 'success', delay: 5000 }
                                                );
                                            }
                                        });

                                        removePushHandlers("data-ai-check-list-complete");
                                        removePushHandlers("data-ai-check-list-error");
                                    }
                                });

                                // Обработчик ошибок
                                addPushHandlers("data-ai-check-list-error", function (data) {
                                    if (data.callTranscriptionId == callTranscriptionId && data.checkListId == checkListId) {
                                        list.hide();
                                        $errorDiv.html(data.error).show();
                                        $spinner.hide();
                                        $button.prop('disabled', false);
                                        $.notify(
                                            { message: 'Ошибка при обработки ИИ', target: '_self' },
                                            { type: 'danger', delay: 5000 }
                                        );
                                        removePushHandlers("data-ai-check-list-complete");
                                        removePushHandlers("data-ai-check-list-error");
                                    }
                                });
                            } else {
                                $errorDiv.html(response.error).show();
                                $spinner.hide();
                                $button.prop('disabled', false);
                            }
                        },
                        error: function () {
                            $errorDiv.text('Произошла ошибка при отправке запроса').show();
                            $spinner.hide();
                            $button.prop('disabled', false);
                        }
                    });
                });
            }
        });
    }
}
