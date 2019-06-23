console.log('admin script loaded');
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
        jQuery.post('https://self-transformations.com/?rest_route=/packages/add', request, function (response) {
            if(response !== null){
                alert('Successfully added package to user! This page will now refresh.');
                location.reload(true);
            }else{
                alert("an error occurred! Please contact Tyson and let him know something is not working.");
            }
        });
    }
}