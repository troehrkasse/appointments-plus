console.log('admin script for packages loaded');

let url = 'https://self-transformations.com';
if (window.location.href.includes('test')) {
    url = 'https://self-transformations.test';
}


// Add new packages to users
function addUserPackage() {
    let user = document.getElementById("user-select").value;
    let package = document.getElementById("package-select").value;
    let quantity_remaining = document.getElementById("quantity-select").value;

    if (quantity_remaining == "" || package == "" || user == "") {
        alert('please fill out all fields and try again');
    } else {
        let request = {
            "user_id": parseInt(user),
            "package_id": parseInt(package),
            "quantity_remaining": parseInt(quantity_remaining)
        };

        jQuery.post(url + '/?rest_route=/packages/add', request, function (response) {
            if(response == true){
                alert('Successfully added package to user! This page will now refresh.');
                location.reload(true);
            }else{
                alert("an error occurred! Please contact Tyson and let him know something is not working.");
                console.log(response);
            }
        });
    }
}

// Modify a package
function modifyUserPackage(package) {
    let quantity_remaining = document.getElementById(package + "-quantity-update").value;
    if (quantity_remaining == "") {
        alert('please enter a number of remaining appointments and try again');
    } else {
        let request = {
            "package_id": package,
            "quantity_remaining": quantity_remaining
        };

        jQuery.post(url + '/?rest_route=/packages/modify', request, function (response) {
            if(response == true){
                alert('Successfully updated package! This page will now refresh.');
                location.reload(true);
            }else{
                alert("an error occurred! Please contact Tyson and let him know something is not working.");
                console.log(response);
            }
        });
    }
}