document.addEventListener("DOMContentLoaded", function(){
	const optIn = document.getElementById(c2c_years_ago_today.option_id);
	const field = document.getElementById("years-ago-today-content-type");
	if(!optIn || !field) return;

	optIn.addEventListener("change", () => {
		if (optIn.checked) {
			field.disabled = false;
			field.removeAttribute('aria-disabled');
		} else {
			field.disabled = true;
			field.setAttribute('aria-disabled', 'true');
		}
	});
});
