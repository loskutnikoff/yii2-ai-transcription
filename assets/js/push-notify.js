import {beforeShow} from "./CallCenterInterestList";
import {processResponse, showError} from "./utils";

let ws = null;
let handlers = {};

export function addPushHandlers(type, func) {
    handlers[type] = func;
}

export function removePushHandlers(type) {
    delete handlers[type];
}

function connect(url, eventFunc) {
    if (ws) {
        return;
    }
    const restart = () => {
        ws = null;
        setTimeout(() => connect(url, eventFunc), 3000);
    };

    ws = new WebSocket(url);
    ws.onmessage = e => eventFunc(JSON.parse(e.data));
    ws.onclose = restart;
    ws.onerror = restart;
}

export function subscribePushNotifies(options) {
    connect(options.subscribeUrl, e => eventProcess(e, {}));

    addPushHandlers("push-notify-new-message", function (data) {
        if (!window.chatIsOpen) {
            $.notify(
                {message: data.text, url: data.url, target: "_self"},
                {type: "info", delay: 10000}
            );
            $(".js-unread-chat-count").text(data.unreadChatCount || "");
        }
    });
}

function eventProcess(data, options) {
    if (data.hasOwnProperty("type") && handlers.hasOwnProperty(data.type)) {
        handlers[data.type](data, options);
    }
}

addPushHandlers("push-call-center-interest-create", function (data, options) {
    console.log(data.url);
    $.getJSON(data.url)
        .done(result => processResponse(result, {
            beforeShow: function () {
                beforeShow(this, options);
            }
        }))
        .fail(e => showError(e));
});

addPushHandlers("push-notify", function (data) {
    $.notify(
        {icon: data.icon, message: data.message, url: data.url, target: "_blank"},
        {type: data.alert, delay: data.delay}
    );
});

addPushHandlers('uploader_push', data => window.dispatchEvent(new CustomEvent('Uploader:push', { detail: data })));

export class PushNotify {
    constructor(url) {
        this.url = url;
        this.handlers = {};
        this.closed = false;
        this._connect();
    }

    addHandler(type, func) {
        this.handlers[type] = func;
    }

    removeHandler(type) {
        delete this.handlers[type];
    }

    close() {
        this.closed = true;
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
        this.handlers = {};
    }

    _connect() {
        this.ws = new WebSocket(this.url);
        this.ws.onmessage = this._handle.bind(this);
        this.ws.onclose = this._restart.bind(this);
        this.ws.onerror = this._restart.bind(this);
    }

    _handle(e) {
        const data = JSON.parse(e.data);
        if (data.hasOwnProperty("type") && this.handlers.hasOwnProperty(data.type)) {
            this.handlers[data.type](data);
        }
    }

    _restart() {
        if (this.ws || this.closed) {
            return;
        }
        this.ws = null;
        setTimeout(() => this._connect(), 3000);
    }
}
