using Newtonsoft.Json;
using System;
using System.Collections.Specialized;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Net;
using System.Reflection;
using System.Runtime.InteropServices;
using System.Security.Cryptography;
using System.Text;
using System.Windows.Forms;
using injection_stuff.Win32;
using Utils;
using versace_loader;

namespace Security
{
    class security_functions
    {
        private static string licensed_user = "[USERNAME]";
        public static string local_sha256 = "";
        private static string random_string(int length)
        {
            Random random = new Random();
            const string chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            return new string(Enumerable.Repeat(chars, length)
              .Select(s => s[random.Next(s.Length)]).ToArray());
        }

        public static void delete_self()
        {
            ProcessStartInfo info = new ProcessStartInfo();
            info.Arguments = "/C choice /C /Y /N /D Y /T 1 & Del " + Application.ExecutablePath;
            info.WindowStyle = ProcessWindowStyle.Hidden;
            info.CreateNoWindow = true;
            info.FileName = "cmd.exe";
            Process.Start(info);
        }

        public static void detect_hosts_file()
        {
            string path = "system32\\drivers\\etc\\hosts";
            string host_file = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.Windows), path);

            if (File.ReadAllText(host_file).Contains("versacehack.xyz"))
            {
                delete_self();
                Environment.Exit(1);
            }
        }


        private static double last_check = c_utility.get_epoch_time();
        public static void prevent_breakpoints()
        {
            double cur_time = c_utility.get_epoch_time();
            if (cur_time - last_check > 1.5) //time is greater than 1500 ms, kill
            {
                delete_self();
                Environment.Exit(1);
            }

            last_check = cur_time;
        }

        public static void detect_hooking()
        {
            if (detect_hooks.is_hooked())
            {
                delete_self();
                Environment.Exit(1);
            }
        }

        public static void detect_http()
        {
            var i0 = Imports.GetModuleHandle("HTTPDebuggerBrowser.dll") != IntPtr.Zero; //this stuff doesn't acctually work
            var i1 = Imports.GetModuleHandle("FiddlerCore4.dll") != IntPtr.Zero;

            if (i0 || i1)
            {
                delete_self();
                Environment.Exit(1);
            }
        }

        public static void do_main_security()
        {
            detect_hooks.setup_cache();
            check_sha256();
            while (true)
            {
                //prevent_module_attachment();
                detect_debugger_attached();
                detect_debugger_list();
                detect_hosts_file();
                //prevent_breakpoints(); //doesn't work with obfuscation
                detect_hooking();
                //detect_http();
            }
        }

        private static string get_sha256()
        {
            using (FileStream stream = File.OpenRead(Application.ExecutablePath))
            {
                SHA256Managed sha = new SHA256Managed();
                byte[] hash = sha.ComputeHash(stream);
                return BitConverter.ToString(hash).Replace("-", String.Empty);
            }
        }


        private static void check_sha256()
        {
            local_sha256 = get_sha256();
            Networking.set_hash(local_sha256);
            var resp = Networking.check_sha(licensed_user, local_sha256);
            string status = (string) resp.status;
            if (status == "failed")
            {
                MessageBox.Show("invalid loader", "invalid loader");
                delete_self();
                Environment.Exit(0);
            }
            else if (status == "update")
            {
                using (WebClient web = new WebClient())
                {
                    var file_name = "VER$ACE_" + random_string(12) + ".rar";
                    web.DownloadFile((string)resp.detail, file_name);
                    MessageBox.Show("new loader downloaded to " + file_name + ". the password is VER$ACE", "new loader");
                    delete_self();
                    Environment.Exit(0);
                }
            }
        }

        [DllImport("kernel32.dll", SetLastError = true, ExactSpelling = true)]
        static extern bool CheckRemoteDebuggerPresent(IntPtr hProcess, ref bool isDebuggerPresent);

        private static void detect_debugger_attached()
        {
            bool is_debugger_present = false;
            CheckRemoteDebuggerPresent(Process.GetCurrentProcess().Handle, ref is_debugger_present);

            if (is_debugger_present)
            {
                delete_self();
                Environment.Exit(1);
            }
        }

        private static void being_debugged()
        {

        }

        private static void detect_debugger_list()
        {
            string[] banned_strings =
            {
            "ollydbg",			// OllyDebug debugger
		    "ProcessHacker",	// Process Hacker
		    "idaq",				// IDA Pro Interactive Disassembler
		    "idaq64",			// IDA Pro Interactive Disassembler
		    "ImmunityDebugger", // ImmunityDebugger
		    "Wireshark",		// Wireshark packet sniffer
		    "dumpcap",			// Network traffic dump tool
		    "HookExplorer",		// Find various types of runtime hooks
		    "ImportREC",		// Import Reconstructor
		    "windbg",			// Microsoft WinDbg
            "dnSpy",
            "x64dbg",
            "x32dbg",
            "x86dbg",
            "Disassembler",
            "IDA",
            "Scylla"
            };

            foreach (Process pc in Process.GetProcesses())
            {
                foreach (string banned_str in banned_strings)
                {
                    var process_name = pc.ProcessName.ToLower();
                    var banned_str_lw = banned_str.ToLower();
                    if (process_name.Contains(banned_str_lw))
                    {
                        //send post that user is debugging
                        delete_self();
                        Environment.Exit(1);
                    }
                }
            }
        }
    }

}
