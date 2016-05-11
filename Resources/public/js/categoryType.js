/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 

 
$(document).ready(function() {
    $( ".role button" ).click(function() {
        $(this).closest('li').remove();
        return false;
    });
        
    $('#add-another-read-role').click(function(e) {
        e.preventDefault();
 
        var rolesList = $('#read-roles-fields-list');
 
        // grab the prototype template
        var newWidget = rolesList.attr('data-prototype');
 
        newWidget = newWidget.replace(/__name__/g, readRolesCount);
        readRolesCount++;
 
        // create a new list element and add it to the list
        var newLi = $('<li></li>').html(newWidget);
        newLi.appendTo(rolesList);
    });
 
    $('#add-another-write-role').click(function(e) {
        e.preventDefault();
 
        var rolesList = $('#write-roles-fields-list');
 
        // grab the prototype template
        var newWidget = rolesList.attr('data-prototype');
        newWidget = newWidget.replace(/__name__/g, writeRolesCount);
        writeRolesCount++;
 
        // create a new list element and add it to the list
        var newLi = $('<li></li>').html(newWidget);
        newLi.appendTo(rolesList);
    });
});
