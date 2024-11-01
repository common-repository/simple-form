// FormFlow CF7 redirection 
document.addEventListener(
    "wpcf7mailsent",
    function (event) {
      const formflow_json = getCookie("formflow_cf7_redirection");
      const formflow_opt = JSON.parse(formflow_json);
      if (formflow_opt && formflow_opt.formflow_whatsapp_number) {
        const newTab = formflow_opt.formflow_new_tab === "true";
        const target = newTab ? "_blank" : "_self";
        const number = formflow_opt.formflow_whatsapp_number;
        const text = formflow_opt.formflow_whatsapp_data

        // console.log(text);

  
        const mobileurl = `https://wa.me/${number}?text=${encodeURIComponent(text)}`;
        const weburl = `https://web.whatsapp.com/send?phone=${number}&text=${encodeURIComponent( text)}`;
  
        const url = window.innerWidth > 1024 ? weburl : mobileurl;
        // const url = newTab ? weburl : mobileurl;
        window.open(url, target);
        eraseCookie("formflow_cf7_redirection");
      }
  
    },
    false
  );
  
  function setCookie(name, value, days) {
    let expires = "";
  
    if (days) {
      const date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      expires = `; expires=${date.toUTCString()}`;
    }
  
    document.cookie = `${name}=${value || ""}${expires}; path=/`;
  }
  
  function getCookie(name) {
    const cookieStr = decodeURIComponent(document.cookie);
    const cookies = cookieStr.split("; ");
  
    for (let i = 0; i < cookies.length; i++) {
      const cookie = cookies[i];
  
      if (cookie.startsWith(`${name}=`)) {
        return cookie.substring(name.length + 1);
      }
    }
    return "";
  }
  
  function eraseCookie(name) {
    setCookie(name, "", -1);
  }