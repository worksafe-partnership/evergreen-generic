class EglMenu {
    constructor() {
        this.appMenu = document.getElementById('app-menu');
        this.pinButton = document.getElementById('pin-toggle');
        // this.menuButton = document.getElementById('menu-toggle');
        this.mainElem = document.getElementById('app-main');
        this.toggleButtons = document.querySelectorAll('.toggle-child, .no-link');
        this.listButtons = document.getElementsByClassName('list-button');

        this.pinButton.addEventListener('mouseup', () => this.toggleState('pinned'));
        // this.menuButton.addEventListener('mouseup', () => this.toggleState('is-active'));
        this.mainElem.addEventListener('mouseup', () => this.unsetState('is-active'));

        this.checkboxes = document.getElementsByClassName('b-checkbox');
        this.defaultConfig = [];

        for (var i = 0; i < this.toggleButtons.length; i++) {
            this.toggleButtons[i].addEventListener('mouseup', (event) => this.toggleDropdown(event));
        }

        for (var i = 0; i < this.listButtons.length; i++) {
            this.listButtons[i].addEventListener('mouseup', (event) => this.toggleDropdown(event));
        }
        
        for (var i = 0; i < this.checkboxes.length; i++) {
            this.checkboxes[i].addEventListener('click', (event) => this.toggleCheckbox(event));
        }

        this.initState();
    }

    toggleCheckbox(e, elem) {
        if (e.target.type == "checkbox") {
            var checkbox = null;
            var value = null;

            if (typeof(e.currentTarget.children) != "undefined") {
                for (var i = 0; i < e.currentTarget.children.length; i++) {
                    var element = e.currentTarget.children[i];
                    if(element.type == "hidden") {
                        value = element;
                    } else if(element.type == "checkbox") {
                        checkbox = element;
                    }
                }
            }

            if (checkbox != null && value != null) {
                if(checkbox.checked) {
                    value.value = 1;
                } else {
                    value.value = '';
                }
            }
        }
    }

    toggleState(attr) {
        if (this.appMenu.classList.contains(attr)) {
            this.appMenu.classList.remove(attr);
            Cookies.set('appMenuState', 0);
            if(attr == 'pinned'){
                // this.menuButton.classList.remove('is-hidden');
                this.appMenu.classList.remove('is-active');
                // Cookies.set('menuButtonState', 1);
                Cookies.set('pinnedState', 0);
            }
        } else {
            this.appMenu.classList.add(attr);
            Cookies.set('appMenuState', 1);
            if(attr == 'pinned'){
                // this.menuButton.classList.add('is-hidden');
                // Cookies.set('menuButtonState', 0);
                Cookies.set('pinnedState', 1);
            }
        }
    }

    unsetState(attr) {
        if(this.appMenu.classList.contains(attr)
            && !this.appMenu.classList.contains('pinned')) {
            this.appMenu.classList.remove(attr);
            Cookies.set('appMenuState', 0);
        }
    }

    toggleDropdown(elem) {
        var scopeId;
        var element = elem.currentTarget;

        if (element.classList.contains('no-link')) {
            scopeId = element.id;
            element = element.nextElementSibling;
        } else {
            scopeId = element.previousElementSibling.id;
        }
        if (element.classList.contains('active')) {
            element.classList.remove('active');
            Cookies.set(scopeId+'DropDown', 0);
        } else {
            element.classList.add('active');
            Cookies.set(scopeId+'DropDown', 1);
        }

        $(".nano").nanoScroller();
    }

    sortFlatPickrConfig(ele, config) {
        for(var x in ele.attributes) {
            var attr = ele.attributes[x];
            if (typeof(this.defaultConfig[attr.name]) != "undefined") {
                config[this.defaultConfig[attr.name]] = attr.value;
            }
        }

        return config;
    }

    sortFlatPickr(elements) {
        for (let i = 0; i < elements.length; ++i) {
            var ele = elements[i];
            var origValue = ele.value;
            if ($(ele).hasClass("flatpickr-input") == false && (typeof($(ele).attr("readonly")) == "undefined")) {
                var config = [];
                config['disableMobile'] = true;
                if(!$(ele).hasClass("flatpickr-input")) {
                    var e = flatpickr(ele, this.sortFlatPickrConfig(ele, config));

                    if (Object.keys(this.defaultConfig).length == 0) {
                        for (var x in e.config) {
                            this.defaultConfig[x.toLowerCase()] = x;
                        }
                        
                        config['onClose'] = function () {
                            var tmpDate = this._input.value;
                            if (this.config.enableTime && this.config.noCalendar) {
                                parsedDate = tmpDate;
                            } else if (this.config.enableTime && !this.config.noCalendar) {
                                tmpDate = tmpDate.split(" ");
                                let date = tmpDate[0].split("/");
                                let time = tmpDate[1].split(":");

                                var parsedDate = new Date(date[2], date[1] - 1, date[0], time[0], time[1]);
                            } else {
                                tmpDate = tmpDate.split("/");

                                var parsedDate = new Date(tmpDate[2], tmpDate[1] - 1, tmpDate[0]);
                            }

                            this.setDate(parsedDate, false);
                        };

                        delete ele._flatpickr;
                        ele.value = origValue;

                        var e = flatpickr(ele, this.sortFlatPickrConfig(ele, config));
                    }
                }
            }
        }
    }

    initState() {
        // if(Cookies.get('appMenuState') == 0){
        //     this.appMenu.classList.remove('is-active');
        // }
        // if(Cookies.get('appMenuState') == undefined || Cookies.get('appMenuState') == 1){
        // }

        // if(Cookies.get('menuButtonState') == 1){ //  || Cookies.get('menuButtonState') != undefined
            // this.menuButton.classList.remove('is-hidden');
        // }

        // if(Cookies.get('pinnedState') == 0){
        //     this.appMenu.classList.remove('pinned');
        //     this.appMenu.classList.remove('is-active');
        // }

        // if(Cookies.get('pinnedState') == undefined || Cookies.get('pinnedState') == 1){
        //     this.appMenu.classList.add('pinned');
        // }

        var childToggles = document.querySelectorAll('.toggle-child, .no-link'), i;

        for (i = 0; i < childToggles.length; ++i) {
            var scopeId, scope;
            if (childToggles[i].classList.contains('no-link')) {
                scopeId = childToggles[i].id;
                scope = childToggles[i];
            } else if(childToggles[i].classList.contains('toggle-child')) {
                scopeId = childToggles[i].previousElementSibling.id;
                scope = childToggles[i].previousElementSibling;
            }

            let childElem = childToggles[i];

            if(Cookies.get(scopeId+'DropDown') == 1 ||
                scope.classList.contains('is-active')){
                childElem.classList.add('active');
            }
        }

        $(".nano").nanoScroller();
        if(!/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            this.sortFlatPickr(document.getElementsByClassName('datepicker'));
            this.sortFlatPickr(document.getElementsByClassName('datetimepicker'));
        }
    }
}

class EglModal {
    toggle(modalElement) {
        let modal = document.querySelector(modalElement);

        if (modal.classList.contains('is-active'))
            modal.classList.remove('is-active');
        else
            modal.classList.add('is-active');
    }
}

window.addEventListener('load', () => new EglMenu());

window.egl = {}
window.egl.modal = new EglModal();
