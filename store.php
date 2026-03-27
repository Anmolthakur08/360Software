<?php
include_once './config.php';
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];       // shortcut

// echo "Welcome, " . htmlspecialchars($user['name']);
// echo "Your e-mail is " . htmlspecialchars($user['email']);
// echo "sub " . htmlspecialchars($user['id']);

?>  

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>360 Software</title>
    <link rel="stylesheet" href="./360Software/360Software.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
     <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
     <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="./360Software/360Software.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <!-- DataTables JS -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    <style>
        .toast-success {
        background-color: #51A351 !important;
        color: white !important;
        }

        .toast-error {
         background-color: #BD362F;
         color: white !important;
        }
        th {
        text-align: center !important;
        }
       .dataTables_wrapper {
       width: 60%;
       }  
    </style>
    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-center",
            "timeOut": "3000"
        };
    </script>
</head>

<body>
    <?php 
include_once './navbar.php';
?>
    <div class="center-container">
        <h1 id="Title">Add a New Store</h1>
        <?php

    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.success('$message');
        });
    </script>";
        unset($_SESSION['message']);
    }
      if (isset($_SESSION['searchplacelistingerror'])) {
        $message = $_SESSION['searchplacelistingerror'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('$message');
        });
    </script>";
        unset($_SESSION['searchplacelistingerror']);
    }
    ///---------------------------savestorepage----------------------------
     if (isset($_SESSION['savestoreError'])) {
        $message = $_SESSION['savestoreError'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('$message');
        });
    </script>";
        unset($_SESSION['savestoreError']);
    }

     if (isset($_SESSION['savestoresuccess'])) {
        $message = $_SESSION['savestoresuccess'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.success('$message');
        });
    </script>";
        unset($_SESSION['savestoresuccess']);
    }
  ///---------------------------deletestorepage----------------------------
         if (isset($_SESSION['deletestoreerror'])) {
        $message = $_SESSION['deletestoreerror'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('$message');
        });
    </script>";
        unset($_SESSION['deletestoreerror']);
    }
    ?>
  
       <button type="button" class=" nav-btn  btn btn-primary" data-bs-toggle="modal" data-bs-target="#placeModals"
                    style="margin-left: 3px;">
                    Add Store Location Name
       </button>

        <div class="modal fade" id="placeModals" tabindex="-1" aria-labelledby="placeModals" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <form id="searchForm" onsubmit="searchPlaces(event)">
                        <label for="searchImage">Place name with their Country</label><br>
                        <input type="text" id="searchImage" name="searchImage"
                            value="" /><br><br>
                        <button type="submit" class="btn btn-primary">Search Place Name</button>
                    </form>

                    <!-- 📋 List of places will appear here -->


                    <!-- ✅ Submission Form -->
                    <form id="submitPlaceForm" method="POST" action="savestore.php">

                        <div id="placeResults" style="margin-top: 20px;"></div>
                        <input type="hidden" id="imagesPlace" name="imagesPlace">

                        <div class="modal-footer mt-3 "id="submitFooter" style="display: none;">
                            <button type="submit" class="btn btn-success">Submit Selected Place</button>
                        </div>
                    </form>

                </div>

            </div>
        </div>
    </div>
<table id="storeTable" class="display" style="width: 100%;">
    <thead>
        <tr>
            <th>Sr.No</th>
            <th>Name</th>
            <th>Date Created</th>
            <th>Last Updated</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        include_once 'config.php';

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $user = $_SESSION['user'];
        $userId = $user['id'];

        $sql = "SELECT id, storeName, dateCreated ,lastUpdate FROM 360g_stores WHERE userId=$userId ORDER BY dateCreated DESC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
        $srNo = 1; 
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>" . $srNo++ . "</td>
                <td><a href='g_storeimages.php?id=" . urlencode($row["id"]) . "'>" . htmlspecialchars(str_replace('_', ' ', $row['storeName'])) . "</a></td>
                <td>" . htmlspecialchars($row["dateCreated"] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($row["lastUpdate"] ?? 'N/A') . "</td>
                <td>
                    <a href='./deleteStore.php?id=" . urlencode($row["id"]) . "' onclick='return confirm(\"Are you sure you want to delete this store?\")'>
                        <i class='fa fa-trash' style='font-size:24px;color:red;'></i>
                    </a>
                </td>
            </tr>";
        }
        }

        $conn->close();
        ?>
    </tbody>
</table>


     <script>
    document.getElementById('submitPlaceForm').addEventListener('submit', function(e) {
    const imagesPlaceValue = document.getElementById('imagesPlace').value.trim();
    
    if (!imagesPlaceValue) {
        e.preventDefault(); // Stop form submission
        alert('Please select a place before submitting.');
       // Reload the page after alert
    }
    });
    </script>
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

     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js"
        integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D"
        crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

      <script>
        function searchPlaces(e) {
    e.preventDefault();
    const query = document.getElementById('searchImage').value.trim();
    const footer = document.getElementById('submitFooter');
    const resultsContainer = document.getElementById('placeResults');
    footer.style.display = 'none'; // hide on new search

    if (!query) return alert("Please enter a place name.");

    const formData = new FormData();
    formData.append('searchImage', query);

    fetch('searchplacelisting.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.text())
        .then(data => {
            resultsContainer.innerHTML = data;
            document.getElementById('imagesPlace').value = '';

            const items = document.querySelectorAll('#placeResults li');
            if (items.length === 0) {
                footer.style.display = 'none'; // still no results
                return;
            }

            // Add click handler to each result item
            items.forEach(item => {
                item.addEventListener('click', function () {
                    items.forEach(li => li.classList.remove('selected'));
                    this.classList.add('selected');

                    document.getElementById('imagesPlace').value = this.dataset.placeName;
                    footer.style.display = 'block'; // Show only when a place is selected
                });
            });
        });
}
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const editButtons = document.querySelectorAll(".edit-store");
            const storeNameInput = document.getElementById("storeName");
            const storeIdInput = document.getElementById("storeId");
            const submitBtn = document.getElementById("submitBtn");
            const TitleHeader= document.getElementById("Title");

            editButtons.forEach(button => {
                button.addEventListener("click", e => {
                    e.preventDefault();
                    const storeName = button.getAttribute("data-name");
                    const storeId = button.getAttribute("data-id");

                    // Fill form inputs
                    storeNameInput.value = storeName;
                    storeIdInput.value = storeId;

                    // Change button text to update mode
                    submitBtn.textContent = "Update Store";
                    TitleHeader.textContent="Update Store Name";
                    // Scroll form into view
                    storeNameInput.scrollIntoView({ behavior: "smooth", block: "center" });
                });
            });
        });
    </script>
    <script>
    $(document).ready(function () {
        $('#storeTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "language": {
            "emptyTable": "No stores found."
             }
        });
    });

    
</script>
</body>

</html>
