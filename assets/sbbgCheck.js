// js function to submit candidate data, posts to backend
// then gets the response, posts to backend again and reloads page on completion

(function($){
    //console.log('js loaded');
    $(document).on('click', '.sbbg-check-submit', function(){
        //event.preventDefault();
        console.log('clicked button');
        var btn = $(this);
        var uID = btn.attr('data-user');
        var fName = btn.attr('data-fname');
        var lName = btn.attr('data-lname');
        var uEmail = btn.attr('data-email');
        var nOnce = btn.attr('data-nonce');
        var zipCode = $('#bg_zipcode').val();
        var bDate = $('#bg_bdate').val();
        var phoneNum = $('#bg_phone').val();
        var ussn = $('#bg_socialNum').val();
        var cAddress = $('#bg_address').val();
        var uCity = $('#bg_city').val();
        var uRegion = $('#bg_region').val();
        var uCountry = $('#bg_country option:selected').val();
        var ajaxurl = btn.attr('data-url');
        var data =  {action: 'check_update_userInfo', nonce: nOnce,
                dateOfBirth: bDate,
                firstName: fName,
                lastName: lName,
                postalCode: zipCode,
                email: uEmail,
                phone: phoneNum,
                ssn: ussn,
                address: cAddress,
                city: uCity,
                region: uRegion,
                country: uCountry};
        //console.log(data);
        JSON.stringify(data);
        $.ajax({
          type: "POST",
          url: ajaxurl,
          dataType: 'json',
          async: false,
          data: data,
          success: function(re){
            //console.log(re);
            var data2 = {action: 'check_make_order',id:re.id};
            JSON.stringify(data2);
            $.ajax({
                type: "POST",
                url: ajaxurl,
                dataType: 'json',
                async: false,
                data: data2,
                success: function(response){
                    //console.log(response);
                    window.location.reload();
                }
              });
          }
        });
    });

})(jQuery);
// check status button 
(function($){
    //console.log('js loaded');
    $(document).on('click', '.sb-bg-order-check', function(){
        //event.preventDefault();
        console.log('clicked button');
        var btn = $(this);
        var nOnce = btn.attr('data-nonce');
        var bID = btn.attr('data-id');
        var ajaxurl = btn.attr('data-url');
        var data =  {nonce: nOnce, id: bID, action: 'check_order_status'};
        //console.log(data);
        JSON.stringify(data);
        $.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            async: false,
            data: data,
            success: function(re){
                alert('Your background check status is : ' + re.status);
                //console.log(re);
            }
          });
          /*
        $.post(ajaxurl, data, function(response){
            alert('Your background check status is : ' + response.status);
        });
        */
    });

})(jQuery);

(function($){
    //console.log('js loaded');
    $(document).on('click', '.sb-bg-order-check-admin', function(){
        //event.preventDefault();
        console.log('clicked button');
        var btn = $(this);
        var nOnce = btn.attr('data-nonce');
        var bID = btn.attr('data-id');
        var ajaxurl = btn.attr('data-url');
        var data =  {nonce: nOnce, id: bID, action: 'check_order_status'};
        //console.log(data);
        JSON.stringify(data);
        $.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            async: false,
            data: data,
            success: function(re){
                alert('background check status is : ' + JSON.stringify(re));
                //console.log(re);
            }
          });
          /*
        $.post(ajaxurl, data, function(response){
            alert('Your background check status is : ' + response.status);
        });
        */
    });

})(jQuery);