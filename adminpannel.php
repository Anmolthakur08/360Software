<?php
include_once './config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
$sql = 'SELECT 360g_stores.id AS store_id, 360g_stores.storename, google_users.name AS user_name, google_users.email AS user_email
FROM 360g_stores 
INNER JOIN google_users ON 360g_stores.userId = google_users.id';
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Pannel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <style>

    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-center",
            "timeOut": "3000"
        };
    </script>

    <style>
        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f7f7f7;
    margin: 0;
    padding: 0;
    text-align: center;
}
 .loader-wrapper {
            position: fixed;
            inset: 0;
            /* top:0; right:0; bottom:0; left:0 */
            display: flex;
            justify-content: center;
            align-items: center;
            background: #ffffff;
            /* match your page bg */
            z-index: 10000;
            /* stay on top of everything */
            transition: opacity .4s ease;
        }

        /* class we’ll add when it’s time to hide */
        .loader-wrapper.hidden {
            opacity: 0;
            pointer-events: none;
            /* clicks pass through if fade takes time */
        }

.page-title {
    margin-top: 30px;
    font-size: 32px;
    color: #333;
}

.table-container {
    display: flex;
    justify-content: center;
    margin: 30px 0;
}

.styled-table {
    width: 60%; /* medium size */
    border-collapse: collapse;
    background-color: white;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.styled-table thead tr {
    background-color: #009879;
    color: #ffffff;
    text-align: center;
}

.styled-table th,
.styled-table td {
    padding: 12px 15px;
    border: 1px solid #dddddd;
    text-align: center;
}

.styled-table tbody tr:nth-child(even) {
    background-color: #f3f3f3;
}

.styled-table tbody tr:hover {
    background-color: #e6f7f4;
}

.btn-delete {
    padding: 6px 12px;
    background-color: #e74c3c;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
}

.btn-delete:hover {
    background-color: #c0392b;
}

</style>
</head>

<body>
    <?php
    include_once './navbar.php';

    if (isset($_SESSION['loginsuccess'])) {
        $message = $_SESSION['loginsuccess'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.success('$message');
        });
    </script>";
        unset($_SESSION['loginsuccess']);
    }
    //-----------------------------admindelete_store----------------------------
    if (isset($_SESSION['admindelstoreerror'])) {
        $message = $_SESSION['admindelstoreerror'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('$message');
        });
    </script>";
        unset($_SESSION['admindelstoreerror']);
    }
    if (isset($_SESSION['admindelstoresuccess'])) {
        $message = $_SESSION['admindelstoresuccess'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.success('$message');
        });
    </script>";
        unset($_SESSION['admindelstoresuccess']);
    }
    ?>
<h1 class="page-title">Admin Panel</h1>

<div class="table-container">
    <table class="styled-table">
        <thead>
            <tr>
                <th>UserName</th>
                <th>Email</th>
                <th>StoreName</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows):
                ?>
            
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr data-store-id="<?= (int)$row['store_id'] ?>">
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= htmlspecialchars($row['user_email']) ?></td>
                        <td><?= htmlspecialchars($row['storename']) ?></td>
                        <td>
                            <button class="btn-delete">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No stores found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


  <script>
        // document.addEventListener('DOMContentLoaded',()=>{
        const loader = document.getElementById('pageLoader');
        // Show loader on page load
        window.addEventListener('load', () => {
            loader.classList.add('hidden');
        });

        // Show loader before unload (navigation)
        window.addEventListener('beforeunload', () => {
            loader.classList.remove('hidden');
        });
        // })

    </script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('.styled-table');

  table.addEventListener('click', async (e) => {
    if (!e.target.classList.contains('btn-delete')) return;

    const row = e.target.closest('tr');
    const storeId = row.dataset.storeId;

    if (!confirm('Are you sure you want to delete this store?')) return;

    try {
      const res = await fetch('admindelete_store.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ storeId })
      });

      const data = await res.json();
      if (data.success) {
        row.remove();  
        window.location.reload();                       
      } else {
        alert('Delete failed: ' + data.error);
      }
    } catch (err) {
      alert('Network or server error');
      console.error(err);
    }
  });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
        crossorigin="anonymous"></script>
</body>
</html>