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
        },
        delete: function(id) {
          $('#categories').on('click','.needjs', function(){
            var subcategory = $(this).attr('subcategory');
            var parent = $(this).attr('parent');
            
            if(subcategory == 1){
              //Es subcategoria
              $( "#alert-category" ).empty();
              $("#selector").empty();
              $("#alert-category").append("<strong>"+"Seguro desea eliminar esta categoria"+"</strong><br>");
            }
            else{
              //Es categoria padre
              $( "#alert-category" ).empty();
              $("#selector").empty();
              $("#alert-category").append("Esta categoria tiene asignadas otras categorias, que desea hacer con las subcategorias<br>");
              $("#selector").append("<form id=selector_option action=''><input type='radio' name='categories' value='1'> Eliminar Todas las subcategorias<br><input type='radio' name='categories' value='2'> Convertir en categoria principal todas las subcategorias<br></form>");
            }

            $('#myModal').modal('show'); 

            $('#eliminar').attr('parent', parent);
            $('#eliminar').attr('subcategory', subcategory);
          });  
          $('#eliminar').click(function () {
            var parent2 = $(this).attr('parent');
            var subcategory2 = $(this).attr('subcategory');
            
            if(subcategory2==0){
              var value = $('input[name=categories]:checked').val();

              if(value ==1 || value ==2){
                window.location.assign("category.php?delete=1&category="+parent2+"&id="+id+"&type="+value);
              }
              else{
                $("#selector").append("<strong>Error:</strong> Debe seleccionar una de las opciones");
              }
            }
            else{
              window.location.assign("category.php?delete=1&category="+parent2+"&id="+id);
            }
        })
        }
     }

});



