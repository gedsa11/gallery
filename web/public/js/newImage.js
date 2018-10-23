var tiposValidos=[
	'image/jpeg',
	'image/png',
	'image/svg'
];

function validarTipos(file){

	for (var i = 0; i < tiposValidos.length; i++) {
		if (file.type == tiposValidos[i]) {
			return true;
		}
	}
	return false;
}

function onChange(event){
	var file= event.target.files[0];
	console.log(file.type);
	if(!validarTipos(file)){
		alert('Por favor ingrese un tipo de imágen válido!');
		 $('#image_image').val('');
	}
	else{
		var pathImg=window.URL.createObjectURL(file);
		 $('#path-img').val(pathImg);	
	}
}