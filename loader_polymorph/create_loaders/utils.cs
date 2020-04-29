using System;
using System.IO;
using System.Net;
using System.Reflection;
using System.Threading;

namespace create_loaders
{
    class utils
    {
        public static void clean_directory(string username)
        {
            const string loader_base = "ldr_base.exe";

            var current_path = Path.GetDirectoryName(Assembly.GetEntryAssembly().Location);
            string[] files = Directory.GetFiles(current_path, "*.exe");
            var current_filename = Assembly.GetEntryAssembly().Location;

            try
            {
                Directory.Delete("VER$ACE_" + username, true);
            }
            catch
            {
                Thread.Sleep(TimeSpan.FromMinutes(2));
                clean_directory(username); //recursive function - stupid idea
            }
        }

        public static string[] get_usernames()
        {
            const string api_url = "https://versacehack.xyz/polymorphic/get_users.php";
            WebClient web = new WebClient();
            web.Headers.Add("user-agent", "VER$ACE-LOADER-BOT");
            var usernames = web.DownloadString(api_url).Split(' ');
            for (int i = 0; i < usernames.Length; i++)
            {
                usernames[i] = usernames[i].Trim();
            }

            return usernames;
        }
    }
}
