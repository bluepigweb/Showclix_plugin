document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('gotham-fullcalendar');
    if (!calendarEl) return;

    var rawData = window.gothamCalEvents || {};
    var allEvents = [];
    var currentFilteredEvents = [];

    // Map our grouped-by-date data to FullCalendar's event format
    Object.keys(rawData).forEach(function (date) {
        rawData[date].forEach(function (ev) {
            allEvents.push({
                title: ev.title,
                start: date,
                url: ev.url,
                timestamp: ev.timestamp,
                extendedProps: {
                    time: ev.time,
                    image: ev.image,
                    timestamp: ev.timestamp
                }
            });
        });
    });
    currentFilteredEvents = allEvents.slice();

    function getLocalDateString(date) {
        var y = date.getFullYear();
        var m = ('0' + (date.getMonth() + 1)).slice(-2);
        var d = ('0' + date.getDate()).slice(-2);
        return y + '-' + m + '-' + d;
    }

    var customListViewEl = document.getElementById('gotham-custom-list-view');

    function renderCustomListView(view) {
        if (!customListViewEl) return;
        if (!view) {
            view = calendar ? calendar.view : null;
        }
        if (!view) return;

        var viewType = view.type;

        if (viewType === 'dayGridMonth') {
            customListViewEl.style.display = 'none';
            calendarEl.style.display = 'block';
            if (calendar) {
                calendar.updateSize();
            }
            return;
        }

        // It's a list view! Hide standard FullCalendar view content, show custom container
        customListViewEl.style.display = 'block';
        calendarEl.style.display = 'none';

        var activeStart = view.activeStart;
        var activeEnd = view.activeEnd;

        // Filter events that fall in the current view's range
        var rangeEvents = currentFilteredEvents.filter(function (ev) {
            var parts = ev.start.split('-');
            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10) - 1;
            var day = parseInt(parts[2], 10);
            var evDate = new Date(year, month, day);

            return evDate >= activeStart && evDate < activeEnd;
        });

        // Group by date
        var grouped = {};
        rangeEvents.forEach(function (ev) {
            if (!grouped[ev.start]) {
                grouped[ev.start] = [];
            }
            grouped[ev.start].push(ev);
        });

        // If no events found, display a friendly "No events found" message
        if (rangeEvents.length === 0) {
            customListViewEl.innerHTML = '<div class="gsc-no-events">No events found for this period.</div>';
            return;
        }

        // Sort the unique dates
        var sortedDates = Object.keys(grouped).sort();

        var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        var html = '';

        sortedDates.forEach(function (dateStr) {
            var parts = dateStr.split('-');
            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10) - 1;
            var day = parseInt(parts[2], 10);
            var dateObj = new Date(year, month, day);

            var dayName = days[dateObj.getDay()];
            var monthName = months[dateObj.getMonth()];
            var dayOfMonth = dateObj.getDate();

            var dayEvents = grouped[dateStr];

            dayEvents.sort(function(a, b) {
                var tA = a.extendedProps ? a.extendedProps.timestamp : 0;
                var tB = b.extendedProps ? b.extendedProps.timestamp : 0;
                return tA - tB;
            });

            html += '<div class="gsc-list-day-container">';

            // Left Column: Date Header
            html += '  <div class="gsc-list-day-header">';
            html += '    <div class="gsc-list-day-name">' + dayName + '</div>';
            html += '    <div class="gsc-list-date-label">' + monthName + ' ' + dayOfMonth + '</div>';
            html += '  </div>';

            // Right Column: Events
            html += '  <div class="gsc-list-day-events">';

            dayEvents.forEach(function (ev) {
                var img = ev.extendedProps.image;
                var time = ev.extendedProps.time;

                html += '    <div class="gsc-list-event-row">';

                // Thumbnail image
                if (img) {
                    html += '      <div class="gsc-list-event-img-col">';
                    html += '        <a href="' + ev.url + '">';
                    html += '          <div class="gsc-list-event-img-wrap">';
                    html += '            <img src="' + img + '" alt="' + ev.title + '" class="gsc-list-event-img">';
                    html += '          </div>';
                    html += '        </a>';
                    html += '      </div>';
                } else {
                    html += '      <div class="gsc-list-event-img-col">';
                    html += '        <div class="gsc-list-event-no-img"></div>';
                    html += '      </div>';
                }

                // Title
                html += '      <div class="gsc-list-event-title-col">';
                html += '        <a href="' + ev.url + '" class="gsc-list-event-title-link">' + ev.title + '</a>';
                html += '      </div>';

                // Time
                html += '      <div class="gsc-list-event-time-col">';
                html += '        <span class="gsc-list-event-time">' + time + '</span>';
                html += '      </div>';

                // Button
                html += '      <div class="gsc-list-event-btn-col">';
                html += '        <a href="' + ev.url + '" class="gsc-list-event-btn">GET TICKETS</a>';
                html += '      </div>';

                html += '    </div>'; // end event row
            });

            html += '  </div>'; // end day events
            html += '</div>'; // end day container
        });

        customListViewEl.innerHTML = html;
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: false, // We use our own custom controls
        events: allEvents,
        eventOrder: 'timestamp',
        datesSet: function (info) {
            // Update our custom month label whenever the view changes
            var label = document.getElementById('gotham-month-label');
            if (label) {
                label.textContent = info.view.title;
            }
            renderCustomListView(info.view);
        },
        dayCellClassNames: function (arg) {
            var dateStr = getLocalDateString(arg.date);
            var hasEvent = currentFilteredEvents.some(function (ev) {
                return ev.start === dateStr;
            });
            return hasEvent ? ['gsc-day-has-events'] : [];
        },
        dateClick: function (info) {
            if (window.innerWidth <= 768) {
                calendar.gotoDate(info.date);
                calendar.changeView('listDay');
                var viewLabel = document.getElementById('gotham-view-label');
                if (viewLabel) {
                    viewLabel.textContent = 'Day View';
                }
                var selectedIconEl = document.querySelector('#gotham-view-dropdown-btn .events-filter-icon');
                if (selectedIconEl) {
                    selectedIconEl.className = 'events-filter-icon day';
                }
            }
        },
        eventContent: function (arg) {
            var ev = arg.event;
            var img = ev.extendedProps.image;
            var time = ev.extendedProps.time;

            // Wrap everything in a link
            var wrapper = document.createElement('a');
            wrapper.href = ev.url;
            wrapper.className = 'gsc-fc-event-link';

            var container = document.createElement('div');
            container.className = 'gsc-fc-event';

			if (img) {
				var imgEl = document.createElement('img');
				imgEl.className = 'gsc-fc-img';
				imgEl.src = img;
				imgEl.alt = 'Event Image';
				container.appendChild(imgEl);
			}

            var infoEl = document.createElement('div');
            infoEl.className = 'gsc-fc-info';

            var title = document.createElement('div');
            title.className = 'gsc-fc-title';
            title.textContent = ev.title;
            infoEl.appendChild(title);

            var timeEl = document.createElement('div');
            timeEl.className = 'gsc-fc-time';
            timeEl.textContent = time;
            infoEl.appendChild(timeEl);

            var btn = document.createElement('div');
            btn.className = 'gsc-fc-btn';
            btn.textContent = 'GET TICKETS';
            infoEl.appendChild(btn);

            container.appendChild(infoEl);
            wrapper.appendChild(container);

            return { domNodes: [wrapper] };
        },
        height: 'auto',
        themeSystem: 'standard'
    });

    calendar.render();

    // ── Custom Navigation ─────────────────────────────────────────────────────
    var prevBtn = document.getElementById('gotham-prev-btn');
    var nextBtn = document.getElementById('gotham-next-btn');

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            calendar.prev();
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            calendar.next();
        });
    }

    // ── Custom View Dropdown ──────────────────────────────────────────────────
    var dropdownBtn = document.getElementById('gotham-view-dropdown-btn');
    var dropdownContent = document.getElementById('gotham-view-dropdown-content');
    var viewLabel = document.getElementById('gotham-view-label');

    if (dropdownBtn && dropdownContent) {
        dropdownBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = dropdownContent.style.display === 'block';
            dropdownContent.style.display = isOpen ? 'none' : 'block';
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function () {
            if (dropdownContent) dropdownContent.style.display = 'none';
        });

        var viewOptions = document.querySelectorAll('.gotham-view-option');
        var viewNames = {
            'dayGridMonth': 'Calendar View',
            'listMonth': 'List View',
            'listDay': 'Day View'
        };

        var viewIcons = {
            'dayGridMonth': 'calendar',
            'listMonth': 'list',
            'listDay': 'day'
        };

        viewOptions.forEach(function (opt) {
            opt.addEventListener('click', function (e) {
                e.stopPropagation();
                var viewId = opt.getAttribute('data-view');
                calendar.changeView(viewId);
                if (viewLabel) viewLabel.textContent = viewNames[viewId] || 'View';
                var selectedIconEl = dropdownBtn.querySelector('.events-filter-icon');
                if (selectedIconEl) {
                    selectedIconEl.className = 'events-filter-icon ' + (viewIcons[viewId] || 'calendar');
                }
                dropdownContent.style.display = 'none';
            });
        });
    }

    // ── Search Filtering ──────────────────────────────────────────────────────
    var searchInput = document.getElementById('gotham-search-input');
    var searchBtn = document.getElementById('gotham-search-btn');

    function applySearch() {
        var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        calendar.removeAllEventSources();
        if (!query) {
            currentFilteredEvents = allEvents.slice();
            calendar.addEventSource(allEvents);
        } else {
            var filtered = allEvents.filter(function (ev) {
                return ev.title.toLowerCase().indexOf(query) !== -1;
            });
            currentFilteredEvents = filtered;
            calendar.addEventSource(filtered);
        }
        renderCustomListView(calendar.view);
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', applySearch);
    }
    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') applySearch();
        });
    }

    // ── Clear Filters ─────────────────────────────────────────────────────────
    var clearBtn = document.getElementById('gotham-clear-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            calendar.removeAllEventSources();
            currentFilteredEvents = allEvents.slice();
            calendar.addEventSource(allEvents);
            renderCustomListView(calendar.view);
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    var container = document.getElementById('gsc-ticket-embed-container');
    if (container) {
        var ticketUrl = container.getAttribute('data-ticket-url');
        if (!ticketUrl) return;

        function normalizeTicketUrl(url) {
            if (!url) return '';
            if (url.indexOf('//') === 0) return window.location.protocol + url;
            if (url.indexOf('http://') === 0 || url.indexOf('https://') === 0) return url;
            return 'https://' + url.replace(/^\/+/, '');
        }

        var resolvedUrl = normalizeTicketUrl(ticketUrl);

        var embedScript = document.createElement('script');
        embedScript.src = resolvedUrl;
        embedScript.async = true;
        embedScript.onerror = function () {
            var loader = document.getElementById('gsc-ticket-loader');
            if (loader) loader.style.display = 'none';
            container.innerHTML = '<p style="padding:30px;text-align:center;font-family:Arial,sans-serif;">'
                + 'Unable to load ticket widget. '
                + '<a href="' + resolvedUrl + '" target="_blank" rel="noopener" style="color:#a40328;font-weight:bold;">'
                + 'Click here to buy tickets &rarr;</a></p>';
        };
        container.appendChild(embedScript);

        var checkLoader = setInterval(function () {
            var iframe = container.querySelector('iframe');
            if (iframe && iframe.contentDocument) {
                var iframeDoc = iframe.contentDocument;
                var htmlTag = iframeDoc.documentElement;

                if (htmlTag) {
                    htmlTag.style.setProperty('--BUTTON__background-color', '#a40328');
                    htmlTag.style.setProperty('--GLOBAL__COLOR__PRIMARY', '#a40328');
                    htmlTag.style.setProperty('--GLOBAL__COLOR__ACCENT', '#a40328');
                }

                var button = iframeDoc.querySelector('button[data-testid="get-tickets"], .button--primary, .banner__cta');
                if (button) {
                    var loader = document.getElementById('gsc-ticket-loader');
                    if (loader) loader.style.display = 'none';
                    clearInterval(checkLoader);
                }
            }
        }, 100);

        setTimeout(function () { clearInterval(checkLoader); }, 15000);
    }
});
