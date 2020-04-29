using System;
using System.Collections.Generic;
using System.Linq;
using System.Runtime.InteropServices;
using System.Text;
using System.Threading.Tasks;

namespace Security
{
    class detect_hooks
    {
        private static Dictionary<string, uint> saved_address = new Dictionary<string, uint>();

        [DllImport("kernel32.dll", SetLastError = true)]
        private static extern bool ReadProcessMemory(IntPtr hProcess, IntPtr lpBaseAddress, IntPtr lpBuffer, int dwSize, out UIntPtr lpNumberOfBytesRead);

        [DllImport("kernel32.dll", SetLastError = true)]
        static extern IntPtr GetCurrentProcess();
        [DllImport("kernel32.dll", CharSet = CharSet.Auto)]
        private static extern IntPtr GetModuleHandle(string lpModuleName);
        [DllImport("kernel32", CharSet = CharSet.Ansi, ExactSpelling = true, SetLastError = true)]
        static extern IntPtr GetProcAddress(IntPtr hModule, string procName);

        private static bool _read_process_memory(IntPtr hProcess, IntPtr lpBaseAddress, byte[] lpBuffer, out UIntPtr lpNumberOfBytesRead)
        {
            var handle = GCHandle.Alloc(lpBuffer, GCHandleType.Pinned);
            var result = ReadProcessMemory(hProcess, lpBaseAddress, handle.AddrOfPinnedObject(), lpBuffer.Length, out lpNumberOfBytesRead);
            handle.Free();
            return result;
        }

        private static bool rpm_wrap_tl(IntPtr hProcess, IntPtr lpBaseAddress, out uint lpBuffer, out UIntPtr lpNumberOfBytesRead)
        {
            var buffer = new byte[4];
            var result = _read_process_memory(hProcess, lpBaseAddress, buffer, out lpNumberOfBytesRead);
            lpBuffer = BitConverter.ToUInt32(buffer, 0);
            return result;
        }

        private static bool cache_addr(string dll_name, string proc_name)
        {
            IntPtr location_to_read = GetProcAddress(GetModuleHandle(dll_name), proc_name);
            uint read;
            bool rpm_success = rpm_wrap_tl(GetCurrentProcess(), location_to_read, out read, out _);
            if (rpm_success)
            {
                saved_address[proc_name] = read;
            }
            //Console.WriteLine("[1] rd: " + read);

            return rpm_success;
        }

        private static bool is_hooked(string dll_name, string proc_name)
        {
            IntPtr location_to_read = GetProcAddress(GetModuleHandle(dll_name), proc_name);
            uint read;
            bool rpm_success = rpm_wrap_tl(GetCurrentProcess(), location_to_read, out read, out _);
            if (!rpm_success)
                return true;

            //Console.WriteLine("[2] rd: " + read + " saved: " + saved_address[proc_name]);
            return saved_address[proc_name] != read;
        }

        public static void setup_cache()
        {
            cache_addr("kernelbase.dll", "ReadProcessMemory");
            cache_addr("kernelbase.dll", "VirtualAlloc");
            cache_addr("kernelbase.dll", "VirtualAllocEx");
            cache_addr("kernelbase.dll", "CreateRemoteThread");
            cache_addr("kernelbase.dll", "WriteProcessMemory");
        }

        public static bool is_hooked()
        {
            if (is_hooked("kernelbase.dll", "ReadProcessMemory"))
                return true;

            if (is_hooked("kernelbase.dll", "VirtualAlloc"))
                return true;

            if (is_hooked("kernelbase.dll", "VirtualAllocEx"))
                return true;

            if (is_hooked("kernelbase.dll", "CreateRemoteThread"))
                return true;

            if (is_hooked("kernelbase.dll", "WriteProcessMemory"))
                return true;

            return false;
        }
    }
}
