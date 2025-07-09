document.addEventListener("DOMContentLoaded", function(){
	const optIn = document.getElementById(c2c_years_ago_today.option_id);
	const field = document.getElementById("years-ago-today-content-type");
	if(!optIn || !field) return;

	optIn.addEventListener("change", () => {
		field.disabled = !optIn.checked;
	});
});
