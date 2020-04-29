using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Security.Cryptography;
using System.Text;
using System.Threading.Tasks;

namespace VER_ACE_toolbox
{
    class Program
    {
        private static string random_string()
        {
            Random random = new Random();
            return new string((from s in Enumerable.Repeat<string>("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789", 16)
                               select s[random.Next(s.Length)]).ToArray<char>());
        }

        private static byte[] generate_random_salt()
        {
            byte[] array = new byte[32];
            using (RNGCryptoServiceProvider rngcryptoServiceProvider = new RNGCryptoServiceProvider())
            {
                for (int i = 0; i < 10; i++)
                {
                    rngcryptoServiceProvider.GetBytes(array);
                }
            }
            return array;
        }

        private static void encrypt_file(string input_file, string output_file, string password)
        {
            byte[] array = generate_random_salt();
            FileStream file_stream = new FileStream(output_file, FileMode.Create);
            byte[] bytes = Encoding.UTF8.GetBytes(password);
            RijndaelManaged rij_managed = new RijndaelManaged();
            rij_managed.KeySize = 256;
            rij_managed.BlockSize = 128;
            rij_managed.Padding = PaddingMode.PKCS7;
            Rfc2898DeriveBytes rfc_derive = new Rfc2898DeriveBytes(bytes, array, 50000);
            rij_managed.Key = rfc_derive.GetBytes(rij_managed.KeySize / 8);
            rij_managed.IV = rfc_derive.GetBytes(rij_managed.BlockSize / 8);
            rij_managed.Mode = CipherMode.CFB;
            file_stream.Write(array, 0, array.Length);
            CryptoStream crypto_stream = new CryptoStream(file_stream, rij_managed.CreateEncryptor(), CryptoStreamMode.Write);
            FileStream file_stream_2 = new FileStream(input_file, FileMode.Open);
            byte[] array2 = new byte[1048576];
            int count;
            while ((count = file_stream_2.Read(array2, 0, array2.Length)) > 0)
            {
                crypto_stream.Write(array2, 0, count);
            }
            file_stream_2.Close();
            crypto_stream.Close();
            file_stream.Close();
        }

        private static string get_sha256(string location)
        {
            using (FileStream stream = File.OpenRead(location))
            {
                SHA256Managed sha = new SHA256Managed();
                byte[] hash = sha.ComputeHash(stream);
                return BitConverter.ToString(hash).Replace("-", String.Empty);
            }
        }

        static void Main(string[] args)
        {

            Console.WriteLine("toolbox options: 1. encrypt file, 2. get sha256 of file");
            string option = Console.ReadLine();
            if (option == "1")
            {
                Console.Write("File to encrypt: ");
                string location = Console.ReadLine();
                string random_string = Program.random_string();
                encrypt_file(location, location + ".enc", random_string);
                Console.ForegroundColor = ConsoleColor.Green;
                Console.WriteLine("File encrypted.");
                Console.ForegroundColor = ConsoleColor.Cyan;
                Console.WriteLine("Key: " + random_string);
            }
            else if (option == "2")
            {
                Console.Write("File to sha256: ");
                string location = Console.ReadLine();
                Console.WriteLine(get_sha256(location));
            }
            Console.ReadLine();
        }
    }
}
