console.log('admin script for appointments loaded');
// Add new packages to users
function addUserAppointment() {
    let user = document.getElementById("user-select").value;
    let appointment = document.getElementById("appointment-select").value;
    let date = document.getElementById("date-select").value;
    let status = document.getElementById("status-select").value;
    

    alert('This function is not quite ready yet, sorry!');
    return;
    if (date == "" || appointment == "" || user == "" || status == "") {
        alert('please fill out all fields and try again');
    } else {
        let request = {
            "user_id": parseInt(user),
            "appointment_id": parseInt(appointment),
            "date": date,
            "status": status
        };

        /*
        jQuery.post('https://self-transformations.test/?rest_route=/appointments/add', request, function (response) {
            if(response == true){
                console.log(response);
                return;
                alert('Successfully added package to user! This page will now refresh.');
                location.reload(true);
            }else{
                alert("an error occurred! Please contact Tyson and let him know something is not working.");
                console.log(response);
            }
        });
        */
    }
}