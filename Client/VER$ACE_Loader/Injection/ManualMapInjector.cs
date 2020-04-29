using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Runtime.InteropServices;
using System.Text;
using System.Threading;
using injection_stuff.Win32;

namespace injection_stuff
{
    internal class manual_map_injector
    {
        #region settings

        public bool async_injection { get; set; } = false;

        public uint time_out { get; set; } = 5000;

        #endregion

        #region fields

        private readonly Process _process;

        private IntPtr _hProcess;

        #endregion

        #region code

        private PIMAGE_DOS_HEADER get_dos_header(IntPtr address)
        {
            var image_dos_headers = (PIMAGE_DOS_HEADER)address;

            if (!image_dos_headers.Value.is_valid)
                return null;

            return image_dos_headers;
        }

        private PIMAGE_NT_HEADERS32 get_nt_header(IntPtr address)
        {
            var image_dos_headers = get_dos_header(address);

            if (image_dos_headers == null)
                return null;

            var image_nt_headers = (PIMAGE_NT_HEADERS32)(address + image_dos_headers.Value.e_lfanew);

            if (!image_nt_headers.Value.is_valid)
                return null;

            return image_nt_headers;
        }

        private IntPtr remote_allocate_memory(uint size)
        {
            return Imports.VirtualAllocEx(_hProcess,
                UIntPtr.Zero,
                new IntPtr(size),
                Imports.AllocationType.Commit | Imports.AllocationType.Reserve,
                Imports.MemoryProtection.ExecuteReadWrite);
        }

        private IntPtr allocate_memory(uint size)
        {
            return Imports.VirtualAlloc(IntPtr.Zero, new UIntPtr(size), Imports.AllocationType.Commit | Imports.AllocationType.Reserve,
                Imports.MemoryProtection.ExecuteReadWrite);
        }

        private IntPtr rva_to_pointer(uint rva, IntPtr base_address)
        {
            var image_nt_headers = get_nt_header(base_address);
            if (image_nt_headers == null)
                return IntPtr.Zero;

            return Imports.ImageRvaToVa(image_nt_headers.Address, base_address, new UIntPtr(rva), IntPtr.Zero);
        }

        private bool inject_dependency(string dependency)
        {
            // standard LoadLibrary, CreateRemoteThread injection
            var proc_address = Imports.GetProcAddress(Imports.GetModuleHandle("kernel32.dll"), "LoadLibraryA");

            if (proc_address == IntPtr.Zero)
            {
#if DEBUG
                Debug.WriteLine("[inject_dependency] GetProcAddress failed");
#endif
                return false;
            }

            var lp_address = remote_allocate_memory((uint)dependency.Length);

            if (lp_address == IntPtr.Zero)
            {
#if DEBUG
                Debug.WriteLine("[inject_dependency] remote_allocate_memory failed");
#endif
                return false;
            }

            var buffer = Encoding.ASCII.GetBytes(dependency);

            UIntPtr bytes_written;
            var result = Imports.WriteProcessMemory(_hProcess, lp_address, buffer, buffer.Length, out bytes_written);

            if (result)
            {
                var hHandle = Imports.CreateRemoteThread(_hProcess, IntPtr.Zero, 0, proc_address, lp_address, 0, IntPtr.Zero);

                if (Imports.WaitForSingleObject(hHandle, time_out) != 0)
                {
#if DEBUG
                    Debug.WriteLine("[inject_dependency] remote thread not signaled");
#endif
                    return false;
                }
            }
#if DEBUG
            else
            {
                Debug.WriteLine("[inject_dependency] WriteProcessMemory failed");
            }
#endif

            Imports.VirtualFreeEx(_hProcess, lp_address, 0, Imports.FreeType.Release);
            return result;
        }

        private IntPtr get_remote_module_handle_a(string module)
        {
            var module_handle = IntPtr.Zero;
            var process_heap = Imports.GetProcessHeap();
            var size = (uint)Marshal.SizeOf(typeof(PROCESS_BASIC_INFORMATION));
            var pbi = (PPROCESS_BASIC_INFORMATION)Imports.HeapAlloc(process_heap, /*HEAP_ZERO_MEMORY*/ 0x8, new UIntPtr(size));

            uint size_needed;
            var status = Imports.NtQueryInformationProcess(_hProcess, /*ProcessBasicInformation*/ 0, pbi.Address, size, out size_needed);

            if (status >= 0 && size < size_needed)
            {
                if (pbi != null)
                    Imports.HeapFree(process_heap, 0, pbi.Address);

                pbi = (PPROCESS_BASIC_INFORMATION)Imports.HeapAlloc(process_heap, /*HEAP_ZERO_MEMORY*/ 0x8, new UIntPtr(size));

                if (pbi == null)
                {
#if DEBUG
                    Debug.WriteLine("[get_remote_module_handle_a] Couldn't allocate heap buffer");
#endif
                    return IntPtr.Zero; //Couldn't allocate heap buffer
                }

                status = Imports.NtQueryInformationProcess(_hProcess, /*ProcessBasicInformation*/ 0, pbi.Address, size_needed, out size_needed);
            }

            if (status >= 0)
            {
                if (pbi.Value.PebBaseAddress != IntPtr.Zero)
                {
                    UIntPtr dwBytesRead;
                    uint peb_ldr_address;

                    if (Imports.ReadProcessMemory(_hProcess, pbi.Value.PebBaseAddress + 12 /*peb.Ldr*/, out peb_ldr_address, out dwBytesRead))
                    {
                        var ldr_list_head = peb_ldr_address + /*InLoadOrderModuleList*/ 0x0C;
                        var ldr_current_node = peb_ldr_address + /*InLoadOrderModuleList*/ 0x0C;

                        do
                        {
                            uint ls_entry_address;
                            if (!Imports.ReadProcessMemory(_hProcess, new IntPtr(ldr_current_node), out ls_entry_address, out dwBytesRead))
                            {
                                Imports.HeapFree(process_heap, 0, pbi.Address);
                            }
                            ldr_current_node = ls_entry_address;

                            UNICODE_STRING ustring_base_dll_name;
                            Imports.ReadProcessMemory(_hProcess, new IntPtr(ls_entry_address) + /*BaseDllName*/ 0x2C, out ustring_base_dll_name, out dwBytesRead);

                            var base_dll_name = string.Empty;

                            if (ustring_base_dll_name.Length > 0)
                            {
                                var buffer = new byte[ustring_base_dll_name.Length];
                                Imports.ReadProcessMemory(_hProcess, ustring_base_dll_name.Buffer, buffer, out dwBytesRead);
                                base_dll_name = Encoding.Unicode.GetString(buffer);
                            }

                            uint dll_base;
                            uint size_of_image;

                            Imports.ReadProcessMemory(_hProcess, new IntPtr(ls_entry_address) + /*DllBase*/ 0x18, out dll_base, out dwBytesRead);
                            Imports.ReadProcessMemory(_hProcess, new IntPtr(ls_entry_address) + /*SizeOfImage*/ 0x20, out size_of_image, out dwBytesRead);

                            if (dll_base != 0 && size_of_image != 0)
                            {
                                if (string.Equals(base_dll_name, module, StringComparison.OrdinalIgnoreCase))
                                {
                                    module_handle = new IntPtr(dll_base);
                                    break;
                                }
                            }

                        } while (ldr_list_head != ldr_current_node);
                    }
                }
            }

            if (pbi != null)
                Imports.HeapFree(process_heap, 0, pbi.Address);

            return module_handle;
        }

        private IntPtr get_dep_proc_address_a(IntPtr module_base, PCHAR procName)
        {
            IntPtr func = IntPtr.Zero;
            IMAGE_DOS_HEADER hdr_dos;
            IMAGE_NT_HEADERS32 hdr_nt32;

            UIntPtr read;
            Imports.ReadProcessMemory(_hProcess, module_base, out hdr_dos, out read);

            if (!hdr_dos.is_valid)
                return IntPtr.Zero;

            Imports.ReadProcessMemory(_hProcess, module_base + hdr_dos.e_lfanew, out hdr_nt32, out read);

            if (!hdr_nt32.is_valid)
                return IntPtr.Zero;

            var exp_base = hdr_nt32.OptionalHeader.ExportTable.VirtualAddress;
            if (exp_base > 0)
            {
                var exp_size = hdr_nt32.OptionalHeader.ExportTable.Size;
                var exp_data = (PIMAGE_EXPORT_DIRECTORY)allocate_memory(exp_size);
                Imports.ReadProcessMemory(_hProcess, module_base + (int)exp_base, exp_data.Address, (int)exp_size, out read);

                var address_of_ords = (PWORD)(exp_data.Address + (int)exp_data.Value.AddressOfNameOrdinals - (int)exp_base);
                var address_of_names = (PDWORD)(exp_data.Address + (int)exp_data.Value.AddressOfNames - (int)exp_base);
                var address_of_funcs = (PDWORD)(exp_data.Address + (int)exp_data.Value.AddressOfFunctions - (int)exp_base);


                for (uint i = 0; i < exp_data.Value.NumberOfFunctions; i++)
                {
                    ushort ord_index;
                    PCHAR name = null;

                    if (new PDWORD(procName.Address).Value <= 0xFFFF)
                        ord_index = unchecked((ushort)i);

                    else if (new PDWORD(procName.Address).Value > 0xFFFF && i < exp_data.Value.NumberOfNames)
                    {
                        name = (PCHAR)new IntPtr(address_of_names[i] + exp_data.Address.ToInt32() - exp_base);
                        ord_index = address_of_ords[i];
                    }
                    else
                        return IntPtr.Zero;

                    if ((new PDWORD(procName.Address).Value <= 0xFFFF && new PDWORD(procName.Address).Value == ord_index + exp_data.Value.Base) || (new PDWORD(procName.Address).Value > 0xFFFF && name.ToString() == procName.ToString()))
                    {
                        func = module_base + (int)address_of_funcs[ord_index];

                        if (func.ToInt64() >= (module_base + (int)exp_base).ToInt64() && func.ToInt64() <= (module_base + (int)exp_base + (int)exp_size).ToInt64())
                        {
                            var forward_str = new byte[255];
                            Imports.ReadProcessMemory(_hProcess, func, forward_str, out read);

                            var chain_exp = Helpers.to_string_ansi(forward_str);

                            var str_dll = chain_exp.Substring(0, chain_exp.IndexOf(".")) + ".dll";
                            var str_name = chain_exp.Substring(chain_exp.IndexOf(".") + 1);

                            var chain_mod = get_remote_module_handle_a(str_dll);
                            if (chain_mod == IntPtr.Zero)
                                inject_dependency(str_dll);

                            if (str_name.StartsWith("#"))
                                func = get_dep_proc_address_a(chain_mod, new PCHAR(str_name) + 1);
                            else
                                func = get_dep_proc_address_a(chain_mod, new PCHAR(str_name));
                        }

                        break;
                    }
                }

                Imports.VirtualFree(exp_data.Address, 0, Imports.FreeType.Release);
            }

            return func;
        }

        private bool process_import_table(IntPtr base_address)
        {
            var image_nt_headers = get_nt_header(base_address);

            if (image_nt_headers == null)
                return false;

            if (image_nt_headers.Value.OptionalHeader.ImportTable.Size > 0)
            {
                var image_import_descriptor = (PIMAGE_IMPORT_DESCRIPTOR)rva_to_pointer(image_nt_headers.Value.OptionalHeader.ImportTable.VirtualAddress, base_address);

                if (image_import_descriptor != null)
                {
                    for (; image_import_descriptor.Value.Name > 0; image_import_descriptor++)
                    {
                        var module_name = (PCHAR)rva_to_pointer(image_import_descriptor.Value.Name, base_address);
                        if (module_name == null)
                            continue;

                        if (module_name.ToString().Contains("-ms-win-crt-"))
                            module_name = new PCHAR("ucrtbase.dll");

                        var module_base = get_remote_module_handle_a(module_name.ToString());
                        if (module_base == IntPtr.Zero)
                        {
                            // todo manual map injection for dependency
                            inject_dependency(module_name.ToString());
                            module_base = get_remote_module_handle_a(module_name.ToString());

                            if (module_base == IntPtr.Zero)
                            {
#if DEBUG
                                Debug.WriteLine("[process_import_table] failed to obtain module handle");
#endif
                                // failed to obtain module handle
                                continue;
                            }
                        }

                        PIMAGE_THUNK_DATA image_thunk_data;
                        PIMAGE_THUNK_DATA image_func_data;

                        if (image_import_descriptor.Value.OriginalFirstThunk > 0)
                        {
                            image_thunk_data = (PIMAGE_THUNK_DATA)rva_to_pointer(image_import_descriptor.Value.OriginalFirstThunk, base_address);
                            image_func_data = (PIMAGE_THUNK_DATA)rva_to_pointer(image_import_descriptor.Value.FirstThunk, base_address);
                        }
                        else
                        {
                            image_thunk_data = (PIMAGE_THUNK_DATA)rva_to_pointer(image_import_descriptor.Value.FirstThunk, base_address);
                            image_func_data = (PIMAGE_THUNK_DATA)rva_to_pointer(image_import_descriptor.Value.FirstThunk, base_address);
                        }

                        for (; image_thunk_data.Value.AddressOfData > 0; image_thunk_data++, image_func_data++)
                        {
                            IntPtr function_address;
                            var snap_by_ordinal = (image_thunk_data.Value.Ordinal & /*IMAGE_ORDINAL_FLAG32*/ 0x80000000) != 0;

                            if (snap_by_ordinal)
                            {
                                var ordinal = (short)(image_thunk_data.Value.Ordinal & 0xffff);
                                function_address = get_dep_proc_address_a(module_base, new PCHAR(ordinal));

                                if (function_address == IntPtr.Zero)
                                    return false;
                            }
                            else
                            {
                                var image_import_by_name = (PIMAGE_IMPORT_BY_NAME)rva_to_pointer(image_func_data.Value.Ordinal, base_address);
                                var name_of_import = (PCHAR)image_import_by_name.Address + /*image_import_by_name->Name*/ 2;
                                function_address = get_dep_proc_address_a(module_base, name_of_import);
                            }

                            //ImageFuncData->u1.Function = (size_t)FunctionAddress;
                            Marshal.WriteInt32(image_func_data.Address, function_address.ToInt32());
                        }
                    }

                    return true;
                }
                else
                {
#if DEBUG
                    Debug.WriteLine("[process_import_table] Size of table confirmed but pointer to data invalid");
#endif
                    // Size of table confirmed but pointer to data invalid
                    return false;
                }
            }
            else
            {
#if DEBUG
                Debug.WriteLine("[process_import_table] no imports");
#endif
                // no imports
                return true;
            }
        }

        private bool process_delayed_import_table(IntPtr base_address)
        {
            var image_nt_headers = get_nt_header(base_address);

            if (image_nt_headers == null)
                return false;

            if (image_nt_headers.Value.OptionalHeader.DelayImportDescriptor.Size > 0)
            {
                var image_delayed_import_descriptor =
                    (PIMAGE_IMPORT_DESCRIPTOR)rva_to_pointer(image_nt_headers.Value.OptionalHeader.DelayImportDescriptor.VirtualAddress, base_address);

                if (image_delayed_import_descriptor != null)
                {
                    for (; image_delayed_import_descriptor.Value.Name > 0; image_delayed_import_descriptor++)
                    {
                        var module_name = (PCHAR)rva_to_pointer(image_delayed_import_descriptor.Value.Name, base_address);
                        if (module_name == null)
                            continue;

                        var module_base = get_remote_module_handle_a(module_name.ToString());
                        if (module_base == IntPtr.Zero)
                        {
                            // todo manual map injection for dependency
                            inject_dependency(module_name.ToString());
                            module_base = get_remote_module_handle_a(module_name.ToString());

                            if (module_base == IntPtr.Zero)
                            {
#if DEBUG
                                Debug.WriteLine("[process_delayed_import_table] no imports");
#endif
                                // failed to obtain module handle
                                continue;
                            }
                        }

                        PIMAGE_THUNK_DATA image_thunk_data = null;
                        PIMAGE_THUNK_DATA image_func_data = null;

                        if (image_delayed_import_descriptor.Value.OriginalFirstThunk > 0)
                        {
                            image_thunk_data = (PIMAGE_THUNK_DATA)rva_to_pointer(image_delayed_import_descriptor.Value.OriginalFirstThunk, base_address);
                            image_func_data = (PIMAGE_THUNK_DATA)rva_to_pointer(image_delayed_import_descriptor.Value.FirstThunk, base_address);
                        }
                        else
                        {
                            image_thunk_data = (PIMAGE_THUNK_DATA)rva_to_pointer(image_delayed_import_descriptor.Value.FirstThunk, base_address);
                            image_func_data = (PIMAGE_THUNK_DATA)rva_to_pointer(image_delayed_import_descriptor.Value.FirstThunk, base_address);
                        }

                        for (; image_thunk_data.Value.AddressOfData > 0; image_thunk_data++, image_func_data++)
                        {
                            IntPtr function_address;
                            var snap_by_ordinal = ((image_thunk_data.Value.Ordinal & /*IMAGE_ORDINAL_FLAG32*/ 0x80000000) != 0);

                            if (snap_by_ordinal)
                            {
                                var ordinal = (short)(image_thunk_data.Value.Ordinal & 0xffff);
                                function_address = get_dep_proc_address_a(module_base, new PCHAR(ordinal));

                                if (function_address == IntPtr.Zero)
                                    return false;
                            }
                            else
                            {
                                var image_import_by_name = (PIMAGE_IMPORT_BY_NAME)rva_to_pointer(image_func_data.Value.Ordinal, base_address);
                                var name_of_import = (PCHAR)image_import_by_name.Address + /*image_import_by_name->Name*/ 2;
                                function_address = get_dep_proc_address_a(module_base, name_of_import);
                            }

                            //ImageFuncData->u1.Function = (size_t)FunctionAddress;
                            Marshal.WriteInt32(image_func_data.Address, function_address.ToInt32());
                        }
                    }

                    return true;
                }
                else
                {
#if DEBUG
                    Debug.WriteLine("[process_delayed_import_table] Size of table confirmed but pointer to data invalid");
#endif
                    // Size of table confirmed but pointer to data invalid
                    return false;
                }
            }
            else
            {
                // no imports
                return true;
            }
        }

        private bool process_relocations(uint imageBaseDelta, ushort data, PBYTE relocationBase)
        {
            var bool_return = true;
            PSHORT raw;
            PDWORD raw2;

            switch ((data >> 12) & 0xF)
            {
                case 1: // IMAGE_REL_BASED_HIGH
                    raw = (PSHORT)(relocationBase + (data & 0xFFF)).Address;
                    Marshal.WriteInt16(raw.Address, unchecked((short)(raw.Value + (uint)((ushort)((imageBaseDelta >> 16) & 0xffff)))));
                    break;

                case 2: // IMAGE_REL_BASED_LOW
                    raw = (PSHORT)(relocationBase + (data & 0xFFF)).Address;
                    Marshal.WriteInt16(raw.Address, unchecked((short)(raw.Value + (uint)((ushort)(imageBaseDelta & 0xffff)))));
                    break;

                case 3: // IMAGE_REL_BASED_HIGHLOW
                    raw2 = (PDWORD)(relocationBase + (data & 0xFFF)).Address;
                    Marshal.WriteInt32(raw2.Address, unchecked((int)(raw2.Value + imageBaseDelta)));
                    break;

                case 10: // IMAGE_REL_BASED_DIR64
                    raw2 = (PDWORD)(relocationBase + (data & 0xFFF)).Address;
                    Marshal.WriteInt32(raw2.Address, unchecked((int)(raw2.Value + imageBaseDelta)));
                    break;

                case 0: // IMAGE_REL_BASED_ABSOLUTE
                    break;

                case 4: // IMAGE_REL_BASED_HIGHADJ
                    break;

                default:
                    bool_return = false;
                    break;
            }

            return bool_return;
        }

        private bool process_relocations(IntPtr base_address, IntPtr remote_address)
        {
            var image_nt_headers = get_nt_header(base_address);

            if (image_nt_headers == null)
                return false;

            if ((image_nt_headers.Value.FileHeader.Characteristics & /*IMAGE_FILE_RELOCS_STRIPPED*/ 0x01) > 0)
                return true;
            else
            {
                var image_base_delta = (uint)(remote_address.ToInt32() - image_nt_headers.Value.OptionalHeader.ImageBase);
                var relocation_size = image_nt_headers.Value.OptionalHeader.BaseRelocationTable.Size;

                if (relocation_size > 0)
                {
                    var relocation_dir = (PIMAGE_BASE_RELOCATION)rva_to_pointer(image_nt_headers.Value.OptionalHeader.BaseRelocationTable.VirtualAddress, base_address);

                    if (relocation_dir != null)
                    {
                        var relocation_end = (PBYTE)relocation_dir.Address + (int)relocation_size;

                        while (relocation_dir.Address.ToInt64() < relocation_end.Address.ToInt64())
                        {
                            var reloc_base = (PBYTE)rva_to_pointer(relocation_dir.Value.VirtualAddress, base_address);
                            var num_relocs = (relocation_dir.Value.SizeOfBlock - 8) >> 1;
                            var reloc_data = (PWORD)((relocation_dir + 1).Address);

                            for (uint i = 0; i < num_relocs; i++, reloc_data++)
                                process_relocations(image_base_delta, reloc_data.Value, reloc_base);

                            relocation_dir = (PIMAGE_BASE_RELOCATION)reloc_data.Address;
                        }
                    }
                    else
                        return false;

                }
            }

            return true;
        }

        private uint get_section_protection(DataSectionFlags characteristics)
        {
            uint result = 0;
            if (characteristics.HasFlag(DataSectionFlags.MemoryNotCached))
                result |= /*PAGE_NOCACHE*/ 0x200;

            if (characteristics.HasFlag(DataSectionFlags.MemoryExecute))
            {
                if (characteristics.HasFlag(DataSectionFlags.MemoryRead))
                {
                    if (characteristics.HasFlag(DataSectionFlags.MemoryWrite))
                        result |= /*PAGE_EXECUTE_READWRITE*/ 0x40;
                    else
                        result |= /*PAGE_EXECUTE_READ*/ 0x20;
                }
                else if (characteristics.HasFlag(DataSectionFlags.MemoryWrite))
                    result |= /*PAGE_EXECUTE_WRITECOPY*/ 0x80;
                else
                    result |= /*PAGE_EXECUTE*/ 0x10;
            }
            else if (characteristics.HasFlag(DataSectionFlags.MemoryRead))
            {
                if (characteristics.HasFlag(DataSectionFlags.MemoryWrite))
                    result |= /*PAGE_READWRITE*/ 0x04;
                else
                    result |= /*PAGE_READONLY*/ 0x02;
            }
            else if (characteristics.HasFlag(DataSectionFlags.MemoryWrite))
                result |= /*PAGE_WRITECOPY*/ 0x08;
            else
                result |= /*PAGE_NOACCESS*/ 0x01;

            return result;
        }

        private bool process_section(char[] name, IntPtr base_address, IntPtr remoteAddress, ulong rawData, ulong virtualAddress, ulong rawSize, ulong virtualSize, uint protectFlag)
        {
            UIntPtr lpNumberOfBytesWritten;
            uint dwOldProtect;

            if (
                !Imports.WriteProcessMemory(_hProcess, new IntPtr(remoteAddress.ToInt64() + (long)virtualAddress), new IntPtr(base_address.ToInt64() + (long)rawData),
                    new IntPtr((long)rawSize), out lpNumberOfBytesWritten))
            {
                return false;
            }

            if (!Imports.VirtualProtectEx(_hProcess, new IntPtr(remoteAddress.ToInt64() + (long)virtualAddress), new UIntPtr(virtualSize), protectFlag, out dwOldProtect))
                return false;

            return true;
        }

        private bool process_sections(IntPtr base_address, IntPtr remote_address)
        {
            var image_nt_headers = get_nt_header(base_address);

            if (image_nt_headers == null)
                return false;

            // skip PE header

            var image_section_header = (PIMAGE_SECTION_HEADER)(image_nt_headers.Address + /*OptionalHeader*/ 24 + image_nt_headers.Value.FileHeader.SizeOfOptionalHeader);
            for (ushort i = 0; i < image_nt_headers.Value.FileHeader.NumberOfSections; i++)
            {
                if (image_section_header[i].Name.ToString() == ".reloc")
                    continue;

                var characteristics = image_section_header[i].Characteristics;

                if (characteristics.HasFlag(DataSectionFlags.MemoryRead) || characteristics.HasFlag(DataSectionFlags.MemoryWrite) || characteristics.HasFlag(DataSectionFlags.MemoryExecute))
                {
                    var protection = get_section_protection(image_section_header[i].Characteristics);
                    process_section(image_section_header[i].Name, base_address, remote_address, image_section_header[i].PointerToRawData,
                        image_section_header[i].VirtualAddress, image_section_header[i].SizeOfRawData, image_section_header[i].VirtualSize, protection);
                }
            }

            return true;
        }

        private bool execute_remote_thread_buffer(byte[] threadData, bool async)
        {
            var lp_address = remote_allocate_memory((uint)threadData.Length);


            if (lp_address == IntPtr.Zero)
                return false;

            UIntPtr bytes_written;
            var result = Imports.WriteProcessMemory(_hProcess, lp_address, threadData, threadData.Length, out bytes_written);

            if (result)
            {
                var hHandle = Imports.CreateRemoteThread(_hProcess, IntPtr.Zero, 0, lp_address, IntPtr.Zero, 0, IntPtr.Zero);

                if (async)
                {
                    var t = new Thread(() =>
                    {
                        Imports.WaitForSingleObject(hHandle, 5000);
                        Imports.VirtualFreeEx(_hProcess, lp_address, 0, Imports.FreeType.Release);
                    })
                    { IsBackground = true };
                    t.Start();
                }
                else
                {
                    Imports.WaitForSingleObject(hHandle, 4000);
                    Imports.VirtualFreeEx(_hProcess, lp_address, 0, Imports.FreeType.Release);
                }
            }

            return result;
        }

        private bool call_entry_point(IntPtr base_address, uint entrypoint, bool async)
        {
            var buffer = new List<byte>();
            //first few are setting up args I guess
            buffer.Add(0x68); //push
            buffer.AddRange(BitConverter.GetBytes(base_address.ToInt32())); //base addr?
            buffer.Add(0x68); //push
            buffer.AddRange(BitConverter.GetBytes(/*DLL_PROCESS_ATTACH*/1));  //dll process attach?
            buffer.Add(0x68); //push
            buffer.AddRange(BitConverter.GetBytes(0)); //0
            buffer.Add(0xB8); //move
            buffer.AddRange(BitConverter.GetBytes(entrypoint)); //entrypoint
            buffer.Add(0xFF); //call
            buffer.Add(0xD0); //eax ^
            buffer.Add(0x33); //xor eax,
            buffer.Add(0xC0); //eax ^
            buffer.Add(0xC2); //ret
            buffer.Add(0x04); //0x ^
            buffer.Add(0x00); //4 ^

            return execute_remote_thread_buffer(buffer.ToArray(), async);
        }

        private bool process_tls_entries(IntPtr base_address, IntPtr remoteAddress)
        {
            UIntPtr dwRead;
            var image_nt_headers = get_nt_header(base_address);

            if (image_nt_headers == null)
                return false;

            if (image_nt_headers.Value.OptionalHeader.TLSTable.Size == 0)
                return true;

            var tls_directory = (PIMAGE_TLS_DIRECTORY32)rva_to_pointer(image_nt_headers.Value.OptionalHeader.TLSTable.VirtualAddress, base_address);

            if (tls_directory == null)
                return true;

            if (tls_directory.Value.AddressOfCallBacks == 0)
                return true;

            var buffer = new byte[0xFF * 4];
            if (!Imports.ReadProcessMemory(_hProcess, new IntPtr(tls_directory.Value.AddressOfCallBacks), buffer, out dwRead))
                return false;

            var tls_callbacks = new PDWORD(buffer);
            var result = true;

            for (uint i = 0; tls_callbacks[i] > 0; i++)
            {
                result = call_entry_point(remoteAddress, tls_callbacks[i], false);

                if (!result)
                    break;
            }

            return result;
        }

        private IntPtr load_image_to_memory(IntPtr base_address)
        {
            var image_nt_headers = get_nt_header(base_address);

            if (image_nt_headers == null)
            {
#if DEBUG
                Debug.WriteLine("[load_image_to_memory] Invalid Image: No IMAGE_NT_HEADERS");
#endif
                // Invalid Image: No IMAGE_NT_HEADERS
                return IntPtr.Zero;
            }

            if (image_nt_headers.Value.FileHeader.NumberOfSections == 0)
            {
#if DEBUG
                Debug.WriteLine("[load_image_to_memory] Invalid Image: No Sections");
#endif
                // Invalid Image: No Sections
                return IntPtr.Zero;
            }

            var rva_low = unchecked((uint)-1);
            var rva_high = 0u;
            var image_section_header = (PIMAGE_SECTION_HEADER)(image_nt_headers.Address + /*OptionalHeader*/
            24 + image_nt_headers.Value.FileHeader.SizeOfOptionalHeader);

            for (uint i = 0; i < image_nt_headers.Value.FileHeader.NumberOfSections; i++)
            {
                if (image_section_header[i].VirtualSize == 0)
                    continue;

                if (image_section_header[i].VirtualAddress < rva_low)
                    rva_low = image_section_header[i].VirtualAddress;

                if (image_section_header[i].VirtualAddress + image_section_header[i].VirtualSize > rva_high)
                    rva_high = image_section_header[i].VirtualAddress + image_section_header[i].VirtualSize;
            }

            var image_size = rva_high - rva_low;

            if (image_nt_headers.Value.OptionalHeader.ImageBase % 4096 != 0)
            {
#if DEBUG
                Debug.WriteLine("[load_image_to_memory] Invalid Image: Not Page Aligned");
#endif
                // Invalid Image: Not Page Aligned
                return IntPtr.Zero;
            }

            if (image_nt_headers.Value.OptionalHeader.DelayImportDescriptor.Size > 0)
            {
#if DEBUG
                Debug.WriteLine("[load_image_to_memory] This method is not supported for Managed executables");
#endif
                // This method is not supported for Managed executables
                return IntPtr.Zero;
            }

            var allocated_remote_memory = remote_allocate_memory(image_size);
            if (allocated_remote_memory == IntPtr.Zero)
            {
#if DEBUG
                Debug.WriteLine("[load_image_to_memory] Failed to allocate remote memory for module");
#endif
                // Failed to allocate remote memory for module
                return IntPtr.Zero;
            }

            if (!process_import_table(base_address))
            {
#if DEBUG
                Debug.WriteLine("[load_image_to_memory] Failed to fix imports");
#endif
                // Failed to fix imports
                return IntPtr.Zero;
            }

            if (!process_delayed_import_table(base_address))
            {
#if DEBUG
                Debug.WriteLine("[load_image_to_memory] Failed to fix delayed imports");
#endif
                // Failed to fix delayed imports
                return IntPtr.Zero;
            }

            if (!process_relocations(base_address, allocated_remote_memory))
            {
#if DEBUG
                Debug.WriteLine("[load_image_to_memory] Failed to process relocations");
#endif
                // Failed to process relocations
                return IntPtr.Zero;
            }

            if (!process_sections(base_address, allocated_remote_memory))
            {
#if DEBUG
                Debug.WriteLine("[load_image_to_memory] Failed to process sections");
#endif
                // Failed to process sections
                return IntPtr.Zero;
            }

            if (!process_tls_entries(base_address, allocated_remote_memory))
            {
#if DEBUG
                Debug.WriteLine("[load_image_to_memory] process_tls_entries Failed");
#endif
                // process_tls_entries Failed
                return IntPtr.Zero;
            }

            if (image_nt_headers.Value.OptionalHeader.AddressOfEntryPoint > 0)
            {
                var dll_entry_point = allocated_remote_memory.ToInt32() + (int)image_nt_headers.Value.OptionalHeader.AddressOfEntryPoint;

                if (!call_entry_point(allocated_remote_memory, (uint)dll_entry_point, async_injection))
                {
#if DEBUG
                    Debug.WriteLine("[load_image_to_memory] Failed to call entrypoint");
#endif
                    return IntPtr.Zero;
                }
            }

            return allocated_remote_memory;
        }

        private GCHandle pin_buffer(byte[] buffer)
        {
            return GCHandle.Alloc(buffer, GCHandleType.Pinned);
        }

        private void free_handle(GCHandle handle)
        {
            if (handle.IsAllocated)
                handle.Free();
        }

        private void open_target()
        {
            _hProcess = Imports.OpenProcess(_process, Imports.ProcessAccessFlags.All);

            if (_hProcess == IntPtr.Zero)
                throw new Exception($"Failed to open handle. Error {Marshal.GetLastWin32Error()}");
        }

        private void close_target()
        {
            if (_hProcess != IntPtr.Zero)
            {
                Imports.CloseHandle(_hProcess);
                _hProcess = IntPtr.Zero;
            }
        }

        #endregion

        #region API

        public manual_map_injector(Process p) { _process = p; }

        public IntPtr Inject(byte[] buffer)
        {
            var handle = new GCHandle();

            // clone buffer
            buffer = buffer.ToArray();

            var result = IntPtr.Zero;
            try
            {
                // verify target
                if (_process == null || _process.HasExited)
                    return result;

                //
                handle = pin_buffer(buffer);
                open_target();

                // inject
                result = load_image_to_memory(handle.AddrOfPinnedObject());
            }
            catch (Exception e)
            {
#if DEBUG
                Debug.WriteLine($"Unexpected error {e}");
#endif
            }
            finally
            {
                // close stuff
                free_handle(handle);
                close_target();
            }

            return result;
        }
        #endregion
    }
}   
