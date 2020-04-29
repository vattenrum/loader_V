using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Net;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using System.Collections.Specialized;
using Utils;

namespace versace_loader.Forms
{
    public partial class Main : Form
    {
        string download_link = string.Empty;
        string dec_key = string.Empty;
        byte[] cheat_file = {};
        public static string username = string.Empty;

        public Main(dynamic login_info)
        {
            InitializeComponent();
            this.FormClosing += (s, e) => Environment.Exit(1);

            DateTime current = TimeZoneInfo.ConvertTimeBySystemTimeZoneId(DateTime.UtcNow, "Eastern Standard Time");
            string end_date = string.Empty;

            if ((int)login_info.time_left == 2000000000)
                end_date = "Never";
            else
            {
                DateTime end = current.AddSeconds((int)login_info.time_left);

                end_date = end.ToString("MMM d, yyyy @ hh:mm tt");
            }

            username_label.Text = "Logged in: " + (string)login_info.username;
            username = login_info.username;
            expiration_label.Text = "Expires: " + end_date;
        }

        private void btnInject_Click(object sender, EventArgs e)
        {
            //moved in here so that HTTPDebugger stuff doesn't show until we hit inject
            dynamic dll_related_info = Networking.get_dll(Authentication.saved_info.username, Authentication.saved_info.password);

            if ((string)dll_related_info.status == "failed")
            {
                Environment.Exit(0);
                return;
            }

            if (c_utility.get_epoch_time() - (double) dll_related_info.time > 30)
            {
                Environment.Exit(0); //preventing replay attacks (i think thats what its called)
                return;
            }

            download_link = Reverse(Encoding.UTF8.GetString(Convert.FromBase64String(Reverse((string)dll_related_info.download))));

            var key = (string)dll_related_info.key;
            dec_key = key.Substring(12, key.Length - 24);

            using (WebClient web = new WebClient())
            {
                web.Headers.Add("user-agent", "VER$ACE");
                ServicePointManager.Expect100Continue = true;
                ServicePointManager.SecurityProtocol = SecurityProtocolType.Tls12;
                cheat_file = web.DownloadData(download_link);
            }
            Hide();
            injection_helper.start_injection_thread(cheat_file, dec_key);
            MessageBox.Show("Injection started.\n\nPlease start csgo!", "VER$ACE", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        public static string Reverse(string s)
        {
            char[] char_array = s.ToCharArray();
            Array.Reverse(char_array);

            return new string(char_array);
        }

        private void FlexForm1_Click(object sender, EventArgs e)
        {

        }
    }

}


