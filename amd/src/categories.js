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
			    		
			    		$(this).attr('estado','abierto');
			    		//como la categoria esta abierta mostramos el icono -
			    		$("#ico"+parent).removeClass('fa fa-plus-square');
			    		$("#ico"+parent).addClass('fa fa-minus-square');	

			    		$.post( "categoryview.php",{"id":id, "parent": parent}, function( data ) {
			    		    

			              	$("#sub"+parent).append("<br>"+data);
			            });
		            }
		            //esta abierto
		            else{
		            	$(this).attr('estado','cerrado');
		            	//como la categoria esta cerrada mostramos el icono +
		            	$("#ico"+parent).removeClass('fa fa-minus-square');
		            	$("#ico"+parent).addClass('fa fa-plus-square');
		            	//eliminamos las subcategorias de la interfaz
		            	$("#sub"+parent).html("");
		            }
		    });
        }


       

     }

});



