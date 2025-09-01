export class Event {
    static events = [];
    static defaultEventName = null;

    static listen(name, callback) {
        if (typeof name === "function") {
            callback = name;
            name = Event.defaultEventName;
        }

        if (!Event.events[name]) {
            Event.events[name] = [];
        }

        Event.events[name].push(callback);
    }

    static listeners(eventNames = [], callback) {
        if (eventNames.length === 0) {
            return;
        }

        eventNames.forEach((name) => {
            Event.listen(name, callback);
        });
    }

    static dispatch(name, arg, updatedKeyName = null) {
        if (Event.events[name]) {
            Event.events[name].forEach((callback) => {
                if (arg) {
                    if (Event.defaultEventName) {
                        if (updatedKeyName) {
                            callback.call(Event, arg, updatedKeyName);
                        } else {
                            callback.call(Event, arg);
                        }
                    } else {
                        callback.call(Event, name, arg);
                    }
                } else {
                    callback.call(Event, name);
                }
            });
        }
    }

    static removeListener(name) {
        if (Event.events[name]) {
            delete Event.events[name];
        }
    }

    static removeListeners(eventNames = []) {
        if (eventNames.length === 0) {
            return;
        }

        eventNames.forEach((name) => {
            Event.removeListener(name);
        });
    }
}
