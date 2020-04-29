using Newtonsoft.Json;
using System;
using System.Collections.Generic;
using System.Collections.Specialized;
using System.Linq;
using System.Management;
using System.Net;
using System.Security.Cryptography;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using Security;

namespace versace_loader
{
    class Networking
    {
        public static string base_url = "https://versacehack.xyz/authentication/";
        public static string build_username = "[USERNAME]";
        public static string loader_hash = "";
        public static void set_hash(string hash)
        {
            loader_hash = hash;
        }

        public static dynamic get_dll(string username, string password)
        {
            string response_string = string.Empty;
            using (WebClient web = new WebClient())
            {
                web.Proxy = null;
                web.Headers.Add("user-agent", "VER$ACE");
                NameValueCollection values = new NameValueCollection();

                values["username"] = aes.encrypt_string(username);
                values["password"] = aes.encrypt_string(password);
                values["hwid"] = aes.encrypt_string(HWID.get_hwid());
                values["sha256"] = loader_hash;
                byte[] response_array = web.UploadValues(base_url + "get_dll.php", values);
                response_string = aes.decrypt_string(Encoding.Default.GetString(response_array));
            }

            dynamic result_data = null;

            try
            {
                result_data = JsonConvert.DeserializeObject(response_string);
                return result_data;
            }
            catch (Exception)
            {
                return null;
            }
        }

        public static dynamic check_sha(string username, string sha256)
        {
            string response_string = string.Empty;
            using (WebClient web = new WebClient())
            {
                web.Proxy = null;
                web.Headers.Add("user-agent", "VER$ACE");
                NameValueCollection values = new NameValueCollection();

                values["username"] = username;
                values["sha256"] = sha256;
                byte[] response_array = web.UploadValues(base_url + "check_tamper.php", values);
                response_string = Encoding.Default.GetString(response_array);
            }

            dynamic result_data = null;

            try
            {
                result_data = JsonConvert.DeserializeObject(response_string);
                return result_data;
            }
            catch (Exception)
            {
                return null;
            }
        }

        public static dynamic Login(string username, string password)
        {
            if (username != build_username)
            {
                MessageBox.Show("This loader was not made for you.", "VER$ACE");
                security_functions.delete_self();
                Environment.Exit(0);
            }
            string response_string = string.Empty;
            using (WebClient web = new WebClient())
            {
                web.Proxy = null;
                web.Headers.Add("user-agent", "VER$ACE");
                NameValueCollection values = new NameValueCollection();

                values["username"] = aes.encrypt_string(username);
                values["password"] = aes.encrypt_string(password);
                values["hwid"] = aes.encrypt_string(HWID.get_hwid());
                values["sha256"] = loader_hash;
                byte[] response_array = web.UploadValues(base_url + "login.php", values);
                response_string = aes.decrypt_string(Encoding.Default.GetString(response_array));
            }

            dynamic result_data = null;

            try
            {
                result_data = JsonConvert.DeserializeObject(response_string);
                return result_data;
            }
            catch (Exception) {
                return null;
            }
        }


        //warning, stuff below this point is outdated and is only supported by the web interface
        //it has the encryption funcs, but they might not actually be used in the server side PHP
        public static dynamic Register(string username, string password1, string password2)
        {

            if (password1 != password2)
            {
                string json_info = "{\"status\":\"failed\",\"detail\":\"password mismatch\"}";
                return JsonConvert.DeserializeObject(json_info);
            }
            string response_string = string.Empty;
            using (WebClient web = new WebClient())
            {
                web.Proxy = null;
                web.Headers.Add("user-agent", "VER$ACE");
                NameValueCollection values = new NameValueCollection();
                values["username"] = username;
                values["password"] = password1;
                byte[] response_array = web.UploadValues(base_url + "register.php", values);
                response_string = Encoding.Default.GetString(response_array);
            }

            dynamic result_data = null;

            try
            {
                result_data = JsonConvert.DeserializeObject(response_string);
                return result_data;
            }
            catch (Exception ex)
            {
                return JsonConvert.DeserializeObject($"{{\"status\":\"failed\",\"detail\":\"{ex.StackTrace}\"}}");
            }
        }

        public static dynamic Redeem(string username, string regkey)
        {

            string response_string = string.Empty;
            using (WebClient web = new WebClient())
            {
                web.Proxy = null;
                web.Headers.Add("user-agent", "VER$ACE");
                NameValueCollection values = new NameValueCollection();
                values["username"] = username;
                values["regkey"] = regkey;
                byte[] response_array = web.UploadValues(base_url + "redeem.php", values);
                response_string = Encoding.Default.GetString(response_array);
            }

            dynamic result_data = null;

            try
            {
                result_data = JsonConvert.DeserializeObject(response_string);
                return result_data;
            }
            catch (Exception ex)
            {
                return JsonConvert.DeserializeObject($"{{\"status\":\"failed\",\"detail\":\"{ex.StackTrace}\"}}");
            }
        }
    }
}