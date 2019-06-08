class jForm
{
	constructor(options)
	{
		this.selector = options.selector;
		this.errors = options.errors;

		this.showErrors();
	}

	showErrors()
	{
		var selector = this.selector;
		var errors = this.errors;

		Object.keys(errors).forEach((i) => {
			errors[i].forEach((data) => {
				let div = $(`${selector}`).find(`input[name='${i}'],textarea[name='${i}'],select[name='${i}']`).parent();
				div.addClass('error');
				div.append('<span class="form-error">'+data.msg+"</span>");

				switch (data.type)
				{
					case 'required':
					case 'empty':
						if (div.children('input,textarea,select').attr('required'))
							div.children('label').html(div.children('label').html()+" *");
					break;

					default: ;
				}
			});
		});
	}
}
