
$(document).ready(function(){

	$(".glyphicon-remove").click(function(){
		if(window.confirm("Вы действительно хотите удалить этот элемент?"))
			return true;
		else
			return false;
	});

});
