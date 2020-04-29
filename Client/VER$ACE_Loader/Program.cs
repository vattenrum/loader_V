using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;
using Security;
using Utils;

namespace versace_loader
{
    static class Program
    {
        /// <summary>
        /// The main entry point for the application.
        /// </summary>
        [STAThread]
        static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);

#if (!DEBUG)
            c_utility.security_thread = new Thread(security_functions.do_main_security);
            c_utility.security_thread.Start();
#endif

            var main_form = new Login();
            randomize_title(main_form);
            main_form.Show();
            Application.Run();
        }

        public static void randomize_title(this Form f)
        {
            Random rnd = new Random();
            const string chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            f.Text = new string(Enumerable.Repeat(chars, rnd.Next(10, 20))
              .Select(s => s[rnd.Next(s.Length)]).ToArray());
        }

    }
}
