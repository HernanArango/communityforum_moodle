define(['jquery'], function($) {
 
    return{
        
        init : function(id) {
   			
        	$( "#categories" ).html("<div id='loader'></div>");
            $.post( "categoryview.php",{"id":id, "parent": 0}, function( data ) {
              	$( "#categories" ).html( data );
            });



        },

        loadCategories: function(id){
        	
        	$( "#categories" ).html("<div id='loader'></div>");
		    $('#categories').on('click', '.enlace', function (){
		    		
		    		
		    		estado = $(this).attr('estado');
		    		parent = $(this).attr('parent');

			    	if (estado =='cerrado') {
			    		$(this).attr('estado','abierto')
			    		
			    		$.post( "categoryview.php",{"id":id, "parent": parent}, function( data ) {
			              	$( "#categories" ).append("<br>"+data);
			            });
		            }
		    });
        }


       

     }

});



