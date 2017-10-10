define(['jquery'], function($) {
 
    return{
        
        init : function(id) {
   

            $.post( "categoryview.php",{"id":id, "parent": 0}, function( data ) {
              	$( "#categories" ).html( data );
            });



        },

        loadCategories: function(id){
        	

		    $('#categories').on('click', '.enlace', function (){
		    		
		    		//idforum = $(this).attr('forum');
		    		parent = $(this).attr('parent');
		    		
		    		$.post( "categoryview.php",{"id":id, "parent": parent}, function( data ) {
		              	$( "#categories" ).append("<br>"+data);
		            });
		    });
        }


       

     }

});



