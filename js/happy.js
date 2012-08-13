(function($) {
    function trim(el) {
        return (''.trim) ? el.val().trim() : $.trim(el.val());
    }

    $.fn.isHappy = function (config) {
        var fields = [], item;

        function getError(error) {
            return $('<span id="' + error.id + '" class="unhappyMessage">' + error.message + '</span>');
        }

        function getErrorsList(errors, id) {
            var list = $('<ul id="' + id + '" class="unhappyMessage"></ul>');

            $.each(errors, function(index, value) {
                $(list).append('<li>' + value.message + '</li>');
            });

            return list;
        }

        function handleSubmit(event) {
            var errors = false, i, l;
            for (i = 0,l = fields.length; i < l; i += 1) {
                if (!fields[i].testValid(true)) {
                    errors = true;
                }
            }
            if (errors) {
                event.stopImmediatePropagation();
                if (isFunction(config.unHappy)) config.unHappy();
                return false;
            } else {
                if (config.testMode && window.console) {
                    console.warn('would have submitted');
                    return false;
                }

                if (isFunction(config.Happy)) return config.Happy();
            }
        }

        function isFunction(obj) {
            return !!(obj && obj.constructor && obj.call && obj.apply);
        }

        function isArray(obj) {
            return obj.constructor === Array;
        }

        function processField(opts, selector) {
            var field = $(selector),
                error = {
                    message: opts.message || '',
                    id: selector.slice(1) + '_unhappy'
                };
            var errorEl = $(error.id).length > 0 ? $(error.id) : getError(error);
            var error_id = $(error.id);

            fields.push(field);
            field.testValid = function (submit) {
                var val, gotFunc, temp,
                    el = $(this),
                    errors = [],
                    error_bool = false,
                    error_bool_in = false;
                    var required = !!el.get(0).attributes.getNamedItem('required') || opts.required,
                    password = (field.attr('type') === 'password'),
                    arg = isFunction(opts.arg) ? opts.arg() : opts.arg;


                $(errorEl).remove();
                errorEl = $(error.id).length > 0 ? $(error.id) : getError(error);
                // clean it or trim it
                if (isFunction(opts.clean)) {
                    val = opts.clean(el.val());
                } else if (!opts.trim && !password) {
                    val = trim(el);
                } else {
                    val = el.val();
                }
                // write it back to the field
                el.val(val);

                // get the value
                gotFunc = ((val.length > 0 || required === 'sometimes') && isFunction(opts.test));

                // check if we've got an error on our hands
                if (submit === true && required === true && val.length === 0) {
                    error_bool = true;
                } else if (gotFunc) {
                    error_bool = !opts.test(val, arg);
                }

                // if there are many test for a field
                if (opts.tests !== undefined && isArray(opts.tests) && val.length > 0) {
                    if (error_bool) errors.push(error); // list of errors will start with the 'main error'
                    $.each(opts.tests, function(index, value) {
                        gotFunc = ((val.length > 0 || required === 'sometimes') && isFunction(value.test));
                        if (gotFunc) {
                            var temp_err = value.test(val, value.arg);
                            if (!temp_err) {
                                errors.push({
                                    message: value.message || ''
                                });
                                error_bool_in = true;
                                error_bool = true;
                            }
                        }
                    });
                    if (error_bool_in) {
                        errorEl = getErrorsList(errors, error.id);
                    }
                }
                if (error_bool) {
                    el.addClass('unhappy').before(errorEl);
                    return false;
                } else {
                    el.removeClass('unhappy');
                    return true;
                }
            };
            field.bind(config.when || 'blur, change', field.testValid);
        }

        for (item in config.fields) {
            processField(config.fields[item], item);
        }

        if (config.submitButton) {
            $(config.submitButton).click(handleSubmit);
        } else {
            this.bind('submit', handleSubmit);
        }
        return this;
    };
})(this.jQuery || this.Zepto);