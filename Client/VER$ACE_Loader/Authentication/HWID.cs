using System;
using System.Collections.Generic;
using System.Linq;
using System.Management;
using System.Security.Cryptography;
using System.Text;
using System.Threading.Tasks;

namespace versace_loader
{
    public class HWID
    {

        private static string generate_sha512_string(string input_string)
        {
            SHA512 sha512 = SHA512Managed.Create();

            byte[] bytes = Encoding.UTF8.GetBytes(input_string);
            byte[] hash = sha512.ComputeHash(bytes);
            StringBuilder sb = new StringBuilder();

            for (int i = 0; i <= hash.Length - 1; i++)
                sb.Append(hash[i].ToString("X2"));

            return sb.ToString();
        }

        private static string cpuID()
        {
            string cpu_info = "";
            ManagementClass manage_class = new ManagementClass("win32_processor");

            ManagementObjectCollection manage_collection = manage_class.GetInstances();

            foreach (ManagementObject manage_obj in manage_collection)
            {
                cpu_info = manage_obj.Properties["Revision"].Value.ToString() + manage_obj.Properties["processorID"].Value.ToString();
                break;
            }
            return cpu_info;
        }

        public static string get_hwid()
        {

            try {
                return generate_sha512_string(cpuID() + Environment.MachineName);
            }
            catch (Exception) {
                return generate_sha512_string(Environment.MachineName);
            }
        }

    }
}
